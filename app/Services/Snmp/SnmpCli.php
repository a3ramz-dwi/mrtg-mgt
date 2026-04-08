<?php
declare(strict_types=1);

namespace App\Services\Snmp;

final class SnmpCli
{
  public function __construct(
    private string $snmpgetBin,
    private string $snmpwalkBin
  ) {}

  public static function fromEnv(): self
  {
    return new self(
      $_ENV['SNMPGET_BIN'] ?? '/usr/bin/snmpget',
      $_ENV['SNMPWALK_BIN'] ?? '/usr/bin/snmpwalk'
    );
  }

  /**
   * @return array{ok:bool, output:string, exit_code:int}
   */
  private function run(array $cmd, int $timeoutSec = 5): array
  {
    $command = implode(' ', array_map('escapeshellarg', $cmd)) . ' 2>&1';
    $descriptors = [
      1 => ['pipe','w'],
    ];
    $proc = proc_open($command, $descriptors, $pipes);
    if (!is_resource($proc)) {
      return ['ok' => false, 'output' => 'Failed to start process', 'exit_code' => 127];
    }

    stream_set_timeout($pipes[1], $timeoutSec);
    $out = stream_get_contents($pipes[1]) ?: '';
    fclose($pipes[1]);

    $exit = proc_close($proc);
    return ['ok' => $exit === 0, 'output' => trim($out), 'exit_code' => $exit];
  }

  public function testV2c(string $ip, string $community): array
  {
    // sysUpTime.0
    $cmd = [
      $this->snmpgetBin,
      '-v2c',
      '-c', $community,
      '-t', '1',
      '-r', '1',
      $ip,
      '1.3.6.1.2.1.1.3.0'
    ];
    return $this->run($cmd, 5);
  }

  public function walkV2c(string $ip, string $community, string $oid): array
  {
    $cmd = [
      $this->snmpwalkBin,
      '-v2c',
      '-c', $community,
      '-t', '2',
      '-r', '1',
      '-On', // numeric OIDs (easier parse)
      $ip,
      $oid
    ];
    return $this->run($cmd, 15);
  }
}
