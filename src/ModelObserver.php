<?php

namespace Laravel\Scout;

class ModelObserver
{
    /**
     * The class names that syncing is disabled for.
     *
     * @var array
     */
    protected static $syncingDisabledFor = [];

    /**
     * Enable syncing for the given class.
     *
     * @param  string  $class
     * @return void
     */
    public static function enableSyncingFor($class)
    {
        unset(static::$syncingDisabledFor[$class]);
    }

    /**
     * Disable syncing for the given class.
     *
     * @param  string  $class
     * @return void
     */
    public static function disableSyncingFor($class)
    {
        static::$syncingDisabledFor[$class] = true;
    }

    /**
     * Determine if syncing is disabled for the given class or model.
     *
     * @param  object|string  $class
     * @return bool
     */
    public static function syncingDisabledFor($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return isset(static::$syncingDisabledFor[$class]);
    }

    /**
     * Handle the created event for the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function created($model)
    {
        if (static::syncingDisabledFor($model)) {
            return;
        }

        $model->searchable();
    }

    /**
     * Handle the updated event for the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function updated($model)
    {
        $this->created($model);
    }

    /**
     * Handle the deleted event for the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function deleted($model)
    {
        if (static::syncingDisabledFor($model)) {
            return;
        }

        if($this->usesSoftDelete($model)){
            $model->addScoutMetadata('__soft_deleted', 1);

            $model->searchable();
        }else{
            $model->unsearchable();
        }
    }

    /**
     * Handle the restored event for the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function restored($model)
    {
        if (static::syncingDisabledFor($model)) {
            return;
        }

        if($this->usesSoftDelete($model)){
            $model->addScoutMetadata('__soft_deleted', 0);
        }

        $model->searchable();
    }

    /**
     * Determine if the given model uses soft deletes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    private function usesSoftDelete($model)
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model));
    }
}
