<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;
use \App\User;
use \App\Media;

use Cog\Contracts\Love\Reactable\Models\Reactable as ReactableContract;
use Cog\Laravel\Love\Reactable\Models\Traits\Reactable;

class Comment extends \BrianFaust\Commentable\Models\Comment implements ReactableContract
{
    use SoftDeletes, Reactable;

    /**
     * Appends extra fields to model
     * 
     * @var array
     */
    protected $appends = [
        'human_ago', 'hash', 'parent_hash'
    ];

    public function getHashAttribute()
    {
        return \Hashids::encode($this->id);
    }

    public function getParentHashAttribute()
    {
        return $this->parent_id ? \Hashids::encode($this->parent_id) : null;
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value)
    {
        $value = \Hashids::decode($value);
        return $this->where($this->getRouteKeyName(), $value)->first();
    }

    public function mediable(){
        return $this->morphMany(Media::class, 'mediable');
    }

    public function notifications()
    {
        return $this->morphMany(UserNotification::class, 'notifiable');
    }

} // end class Comment