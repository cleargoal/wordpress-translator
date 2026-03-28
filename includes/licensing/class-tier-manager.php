<?php
namespace WPSTE\Licensing;

if (!defined('ABSPATH')) {
    exit;
}

class Tier_Manager
{
    protected $storage;

    public function __construct()
    {
        $this->storage = new License_Storage();
    }

    public function get_tier()
    {
        return $this->storage->get_tier();
    }

    public function has_feature($feature)
    {
        // Free tier has all core features for now
        return true;
    }

    public function get_language_limit()
    {
        $tier = $this->get_tier();
        if ($tier === 'free') {
            return 3;
        }
        return -1; // unlimited
    }
}
