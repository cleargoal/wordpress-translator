<?php
namespace WPSTE\KeyManagement;

if (!defined('ABSPATH')) {
    exit;
}

class Azure_Key_Manager implements Key_Manager_Interface
{
    protected $provider = 'azure';
    protected $database;

    public function __construct()
    {
        $this->database = new \WPSTE\Database\Database();
    }

    public function get_next_key(): ?array
    {
        $keys = $this->get_all_keys(true);
        return empty($keys) ? null : ['id' => $keys[0]['id'], 'api_key' => $keys[0]['api_key']];
    }

    public function add_key(string $api_key, string $label = '', ?int $quota_limit = null)
    {
        return $this->database->insert('api_keys', [
            'provider' => $this->provider,
            'api_key' => $api_key,
            'label' => $label ?: 'Azure Key',
            'is_active' => 1,
        ]);
    }

    public function remove_key(int $key_id): bool
    {
        return (bool)$this->database->delete('api_keys', ['id' => $key_id, 'provider' => $this->provider]);
    }

    public function get_all_keys(bool $active_only = true): array
    {
        $where = ['provider' => $this->provider];
        if ($active_only) $where['is_active'] = 1;
        return $this->database->get_results('api_keys', $where, ARRAY_A);
    }

    public function update_usage(int $key_id, int $characters): bool
    {
        return (bool)$this->database->query($this->database->wpdb->prepare(
            "UPDATE {$this->database->get_table_name('api_keys')}
            SET usage_count = usage_count + 1, characters_used = characters_used + %d
            WHERE id = %d",
            $characters, $key_id
        ));
    }

    public function has_quota(int $key_id): bool
    {
        return true; // Azure has different quota model
    }

    public function set_active(int $key_id, bool $active): bool
    {
        return (bool)$this->database->update('api_keys', ['is_active' => $active ? 1 : 0], ['id' => $key_id]);
    }

    public function get_provider_name(): string
    {
        return $this->provider;
    }
}
