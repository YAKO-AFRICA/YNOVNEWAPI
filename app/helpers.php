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
        function RefgenerateCode($model, $init, $key)
        {
            $query = $model::query();

            if (in_array(
                \Illuminate\Database\Eloquent\SoftDeletes::class,
                class_uses_recursive($model)
            )) {
                $query->withTrashed();
            }

            $createdAtColumn = (new $model)->getCreatedAtColumn();

            // Générer un code unique
            $attempts = 0;
            $maxAttempts = 10;

            while ($attempts < $maxAttempts) {
                $code = $init . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3)) . rand(10, 99);

                // Vérifier si le code existe déjà
                $exists = $query->where($key, $code)->exists();

                if (!$exists) {
                    return $code;
                }

                $attempts++;
            }

            // Si après 10 tentatives on n'a toujours pas de code unique, utiliser un timestamp
            return $init . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3)) . time();
        }
    }



