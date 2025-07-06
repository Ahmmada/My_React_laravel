<?php

namespace App\Services;

use App\Models\{CardType, HousingType, Center, SocialState, LevelState};
use Illuminate\Support\Facades\Cache;

class FilterOptionsService
{
    public static function get()
    {
        return Cache::remember('filter_options', now()->addHours(12), function () {
            return [
                'cardTypes' => CardType::select('id', 'name')->get(),
                'housingTypes' => HousingType::select('id', 'name')->get(),
                'centers' => Center::select('id', 'name')->get(),
                'socialStates' => SocialState::select('id', 'name')->get(),
                'levelStates' => LevelState::select('id', 'name')->get(),
            ];
        });
    }
}