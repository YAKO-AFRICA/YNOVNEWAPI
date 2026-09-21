<?php
// helper.php

    if (!function_exists('Refgenerate')) {
        function Refgenerate($model, $prefix, $key)
        {
            $query = $model::query();

            if (in_array(
                \Illuminate\Database\Eloquent\SoftDeletes::class,
                class_uses_recursive($model)
            )) {
                $query->withTrashed();
            }

            $latest = $query
                ->where($key, 'like', $prefix . '-%')
                ->orderByDesc('created_at')
                ->first();

            if (!$latest || empty($latest->{$key})) {
                return $prefix . '-00001';
            }

            $number = (int) preg_replace('/[^0-9]/', '', $latest->{$key});

            return $prefix . '-' . sprintf('%05d', $number + 1);
        }
    }

    if (!function_exists('RefgenerateCode')) {
        function RefgenerateCode($table, $init, $key)
        {
            $latest = $table::orderBy('id', 'desc')->first();
            if (!$latest) {
                $code = $init . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3)) . rand(10, 99);
                return $code;
            }

            $string = preg_replace("/[^0-9\.]/", '', $latest->$key);
            $code = $init . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3)) . rand(10, 99);
            return $code;
        }
    }



