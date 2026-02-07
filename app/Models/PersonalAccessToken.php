<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends Model
{
    public function save(array $options = [])
    {
        if (count($this->getDirty()) === 1 && $this->isDirty('last_used_at')) {
            return false;
        }

        return parent::save($options);
    }
}
