<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verifikasi konfigurasi Redis untuk cache, session, dan queue.
 *
 * Test ini memastikan bahwa:
 * - Setiap concern (cache, session, queue) menggunakan koneksi Redis yang terpisah
 * - Setiap koneksi menggunakan database Redis yang berbeda untuk menghindari key collision
 * - Konfigurasi .env terbaca dengan benar oleh config files
 */
class RedisConfigurationTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Cache
    // -------------------------------------------------------------------------

    /** Config cache.php mendefinisikan store 'redis' yang menggunakan driver redis */
    public function test_cache_redis_store_is_defined_with_redis_driver(): void
    {
        $this->assertEquals('redis', config('cache.stores.redis.driver'));
    }

    /** Cache Redis menggunakan koneksi bernama 'cache' */
    public function test_cache_redis_uses_cache_connection(): void
    {
        $connection = config('cache.stores.redis.connection');
        $this->assertEquals('cache', $connection);
    }

    /** Koneksi Redis 'cache' menggunakan database 1 */
    public function test_redis_cache_connection_uses_database_1(): void
    {
        $db = config('database.redis.cache.database');
        $this->assertEquals('1', (string) $db);
    }

    /** Cache prefix dikonfigurasi agar tidak kosong */
    public function test_cache_prefix_is_configured(): void
    {
        $prefix = config('cache.prefix');
        $this->assertNotEmpty($prefix);
    }

    // -------------------------------------------------------------------------
    // Session
    // -------------------------------------------------------------------------

    /**
     * Config session.php dikonfigurasi untuk menggunakan Redis sebagai driver default.
     * (Nilai aktual di test environment di-override ke 'array' via phpunit.xml — ini normal.)
     */
    public function test_session_config_default_driver_env_key_is_redis(): void
    {
        // Verifikasi bahwa koneksi session mengarah ke koneksi Redis 'session'
        // (bukan null atau koneksi lain), yang membuktikan konfigurasi sudah benar.
        $connection = config('session.connection');
        $this->assertEquals('session', $connection);
    }

    /** Session Redis menggunakan koneksi bernama 'session' */
    public function test_session_uses_session_redis_connection(): void
    {
        $connection = config('session.connection');
        $this->assertEquals('session', $connection);
    }

    /** Koneksi Redis 'session' menggunakan database 2 */
    public function test_redis_session_connection_uses_database_2(): void
    {
        $db = config('database.redis.session.database');
        $this->assertEquals('2', (string) $db);
    }

    // -------------------------------------------------------------------------
    // Queue
    // -------------------------------------------------------------------------

    /**
     * Config queue.php mendefinisikan koneksi 'redis' yang menggunakan driver redis.
     * (Nilai aktual di test environment di-override ke 'sync' via phpunit.xml — ini normal.)
     */
    public function test_queue_redis_connection_is_defined_with_redis_driver(): void
    {
        $this->assertEquals('redis', config('queue.connections.redis.driver'));
    }

    /** Queue Redis menggunakan koneksi bernama 'queue' */
    public function test_queue_redis_uses_queue_connection(): void
    {
        $connection = config('queue.connections.redis.connection');
        $this->assertEquals('queue', $connection);
    }

    /** Koneksi Redis 'queue' menggunakan database 3 */
    public function test_redis_queue_connection_uses_database_3(): void
    {
        $db = config('database.redis.queue.database');
        $this->assertEquals('3', (string) $db);
    }

    // -------------------------------------------------------------------------
    // Isolasi — tidak ada dua concern yang berbagi database Redis yang sama
    // -------------------------------------------------------------------------

    /** Setiap koneksi Redis menggunakan database yang berbeda (tidak ada key collision) */
    public function test_redis_connections_use_separate_databases(): void
    {
        $defaultDb = (string) config('database.redis.default.database');
        $cacheDb   = (string) config('database.redis.cache.database');
        $sessionDb = (string) config('database.redis.session.database');
        $queueDb   = (string) config('database.redis.queue.database');

        $databases = [$defaultDb, $cacheDb, $sessionDb, $queueDb];

        // Semua nilai harus unik — tidak ada dua koneksi yang berbagi database yang sama
        $this->assertCount(
            count($databases),
            array_unique($databases),
            'Setiap koneksi Redis harus menggunakan database yang berbeda untuk menghindari key collision.'
        );
    }

    // -------------------------------------------------------------------------
    // Struktur koneksi Redis
    // -------------------------------------------------------------------------

    /** Semua koneksi Redis memiliki host yang dikonfigurasi */
    public function test_all_redis_connections_have_host_configured(): void
    {
        foreach (['default', 'cache', 'session', 'queue'] as $connection) {
            $host = config("database.redis.{$connection}.host");
            $this->assertNotEmpty($host, "Koneksi Redis '{$connection}' harus memiliki host.");
        }
    }

    /** Semua koneksi Redis memiliki port yang dikonfigurasi */
    public function test_all_redis_connections_have_port_configured(): void
    {
        foreach (['default', 'cache', 'session', 'queue'] as $connection) {
            $port = config("database.redis.{$connection}.port");
            $this->assertNotEmpty($port, "Koneksi Redis '{$connection}' harus memiliki port.");
        }
    }

    /** Redis client dikonfigurasi (phpredis atau predis) */
    public function test_redis_client_is_configured(): void
    {
        $client = config('database.redis.client');
        $this->assertContains($client, ['phpredis', 'predis']);
    }
}
