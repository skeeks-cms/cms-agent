<?php

namespace skeeks\cms\agent\helpers;

use skeeks\cms\agent\models\CmsAgentModel;
use skeeks\cms\job\models\CmsJobRun;
use yii\db\Query;

/** Read-only filter snapshot; uses the same effective type and dedup key as JobButton. */
class ScheduleFilters
{
    private $snapshot;

    public function apply($query, string $attribute, $value): void
    {
        if ($value === null || $value === '' || $value === []) { return; }
        $values = (array)$value;
        $ids = [];
        foreach ($this->snapshot() as $id => $state) {
            if (in_array($state[$attribute] ?? null, $values, true)) { $ids[] = $id; }
        }
        $query->andWhere([CmsAgentModel::tableName().'.id' => $ids]);
    }

    private function snapshot(): array
    {
        if ($this->snapshot !== null) { return $this->snapshot; }
        $this->snapshot = [];
        $siteId = \Yii::$app->skeeks->site->id;
        $agents = CmsAgentModel::find()->where(['cms_site_id' => $siteId])->all();
        $keys = [];
        $jobs = \Yii::$app->has('jobs') && class_exists(CmsJobRun::class) ? \Yii::$app->jobs : null;
        foreach ($agents as $agent) {
            $this->snapshot[$agent->id] = [
                'execution_mode' => $agent->isJobBased ? 'job' : 'console',
                'execution_state' => $agent->isJobBased ? 'unknown' : ($agent->is_running ? 'running' : 'idle'),
                'execution_result' => 'unknown',
            ];
            if (!$agent->isJobBased || !$jobs || !$jobs->getRegistry()->has($agent->effectiveJobType)) { continue; }
            $definition = $jobs->getRegistry()->get($agent->effectiveJobType);
            if ($definition->permission && !\Yii::$app->user->can($definition->permission)) { continue; }
            try { $keys[$agent->id] = [$agent->jobDedupKey, $agent->effectiveJobType]; }
            catch (\InvalidArgumentException $e) { continue; }
        }
        if (!$keys) { return $this->snapshot; }

        // One bounded result per key/type plus unfinished runs, rather than loading history or N queries.
        $dedupKeys = array_values(array_unique(array_column($keys, 0)));
        $latest = (new Query())->select('MAX(id)')->from(CmsJobRun::tableName())
            ->where(['cms_site_id' => $siteId, 'dedup_key' => $dedupKeys])
            ->groupBy(['dedup_key', 'job_type']);
        $rows = (new Query())->from(CmsJobRun::tableName())
            ->select(['id', 'cms_site_id', 'dedup_key', 'dedup_active', 'job_type', 'status'])
            ->where(['or', ['id' => $latest], ['dedup_active' => $dedupKeys]])->all();
        $active = $history = [];
        foreach ($rows as $row) {
            if ($row['dedup_active'] !== null) { $active[$row['dedup_active']] = $row; }
            if ((int)$row['cms_site_id'] === (int)$siteId) {
                $key = $row['dedup_key'].'|'.$row['job_type'];
                if (!isset($history[$key]) || $history[$key]['id'] < $row['id']) { $history[$key] = $row; }
            }
        }
        foreach ($keys as $id => [$key, $type]) {
            $run = $active[$key] ?? $history[$key.'|'.$type] ?? null;
            // Shared resources may belong to another site; mirror the status endpoint's nondisclosure.
            if ($run && (int)$run['cms_site_id'] !== (int)$siteId) { continue; }
            $status = $run['status'] ?? null;
            $this->snapshot[$id]['execution_state'] = in_array($status, ['running', 'queued'], true) ? $status : 'idle';
            $this->snapshot[$id]['execution_result'] = [
                'failed' => 'error', 'timed_out' => 'error',
                'succeeded_with_warnings' => 'warning', 'succeeded' => 'success',
                'cancelled' => 'cancelled',
            ][$status] ?? 'unknown';
        }
        return $this->snapshot;
    }
}
