<?php

namespace Tests\Unit;

use App\Services\MigrationReadiness;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\TestCase;

class MigrationReadinessTest extends TestCase
{
    public function test_it_reports_source_only_target_only_and_changed_rows(): void
    {
        $source = $this->connection('source');
        $target = $this->connection('target');
        $this->createInventoryTable($source);
        $this->createInventoryTable($target);

        $source->table('assets')->insert([
            ['id' => 1, 'name' => 'Igual'],
            ['id' => 2, 'name' => 'Cambio anterior'],
            ['id' => 3, 'name' => 'Solo anterior'],
        ]);
        $target->table('assets')->insert([
            ['id' => 1, 'name' => 'Igual'],
            ['id' => 2, 'name' => 'Cambio Laravel'],
            ['id' => 4, 'name' => 'Solo Laravel'],
        ]);

        $report = (new MigrationReadiness())->audit($source, $target, ['assets']);
        $table = $report['tables'][0];

        $this->assertFalse($report['ready']);
        $this->assertSame([3], $table['source_only']);
        $this->assertSame([4], $table['target_only']);
        $this->assertSame([2], $table['changed']);
    }

    public function test_it_ignores_only_the_storage_paths_transformed_during_import(): void
    {
        $source = $this->connection('source_paths');
        $target = $this->connection('target_paths');

        foreach ([$source, $target] as $connection) {
            $connection->getSchemaBuilder()->create('tutorials', function ($table): void {
                $table->unsignedInteger('id')->primary();
                $table->string('title');
                $table->text('content_url')->nullable();
                $table->timestamps();
            });
        }

        $source->table('tutorials')->insert(['id' => 1, 'title' => 'Guia', 'content_url' => 'uploads/guia.pdf']);
        $target->table('tutorials')->insert(['id' => 1, 'title' => 'Guia', 'content_url' => 'laravel-local:library/guia.pdf']);

        $report = (new MigrationReadiness())->audit($source, $target, ['tutorials']);

        $this->assertTrue($report['ready']);
        $this->assertSame([], $report['tables'][0]['changed']);
    }

    private function connection(string $name): ConnectionInterface
    {
        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:'], $name);

        return $capsule->getConnection($name);
    }

    private function createInventoryTable(ConnectionInterface $connection): void
    {
        $connection->getSchemaBuilder()->create('assets', function ($table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('name');
            $table->timestamps();
        });
    }
}
