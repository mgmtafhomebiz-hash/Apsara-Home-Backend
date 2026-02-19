<?php

namespace App\Providers;

use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Override PostgreSQL connector to inject Neon endpoint into DSN
        $this->app->bind('db.connector.pgsql', function () {
            return new class extends PostgresConnector {
                protected function getDsn(array $config): string
                {
                    $dsn = parent::getDsn($config);

                    // Neon SNI workaround: inject endpoint ID into DSN options
                    if (!empty($config['neon_endpoint'])) {
                        $dsn .= ";options='" . $config['neon_endpoint'] . "'";
                    }

                    return $dsn;
                }
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
