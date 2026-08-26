<?php

namespace htrxuan\hdspin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stateless server-side weighted prize selection. The client never supplies
 * or influences which segment is picked -- this is the only place that decides.
 */
class HDSPIN_Prize_Engine
{

    /**
     * @param array $segments Fixed 8-element array of ['enabled','label','type','amount','weight'].
     * @return int Winning segment index (0-7), or -1 if nothing is eligible (misconfiguration).
     */
    public static function pick_segment(array $segments)
    {
        $eligible = array();
        foreach ($segments as $index => $segment) {
            if (!empty($segment['enabled']) && (int) $segment['weight'] > 0) {
                $eligible[$index] = (int) $segment['weight'];
            }
        }

        $total = array_sum($eligible);
        if ($total <= 0) {
            return -1;
        }

        $roll    = wp_rand(1, $total);
        $running = 0;

        foreach ($eligible as $index => $weight) {
            $running += $weight;
            if ($roll <= $running) {
                return $index;
            }
        }

        // Defensive fallback; should be unreachable given the loop above.
        return array_key_first($eligible);
    }
}
