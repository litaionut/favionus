<?php
// Run: php tests/test-fleet-core.php wp-content/themes/doppler_lidar_v2/inc/fleet-core.php
require $argv[1];
$fail=0; function t($name,$got,$exp){global $fail; if($got!==$exp){$fail++; echo "FAIL $name: got ".var_export($got,true)." exp ".var_export($exp,true)."\n";} else echo "ok   $name\n";}
$u=[['id'=>1,'status'=>'active'],['id'=>2,'status'=>'active'],['id'=>3,'status'=>'maintenance'],['id'=>4,'status'=>'active']];
$b=[['unit_id'=>1,'start_date'=>'2026-11-01','end_date'=>'2026-11-30'],['unit_id'=>2,'start_date'=>'2026-12-15','end_date'=>'2027-01-15'],['unit_id'=>1,'start_date'=>'2027-03-01','end_date'=>'2027-03-31']];
t('no bookings in period',doppler_lidar_fleet_count_free($u,$b,'2026-10-01','2026-10-31'),3);
t('overlap start edge',doppler_lidar_fleet_count_free($u,$b,'2026-10-15','2026-11-01'),2);
t('overlap end edge',doppler_lidar_fleet_count_free($u,$b,'2026-11-30','2026-12-05'),2);
t('day after booking',doppler_lidar_fleet_count_free($u,$b,'2026-12-01','2026-12-14'),3);
t('two units busy',doppler_lidar_fleet_count_free($u,$b,'2026-11-20','2026-12-20'),1);
t('maintenance never counted',doppler_lidar_fleet_count_free([['id'=>3,'status'=>'maintenance']],[], '2026-10-01','2026-10-02'),0);
t('no units',doppler_lidar_fleet_count_free([],[], '2026-10-01','2026-10-02'),0);
t('booking inside period',doppler_lidar_fleet_count_free($u,$b,'2027-01-01','2027-12-31'),1);
t('valid date',doppler_lidar_fleet_valid_date('2026-02-28'),true);
t('invalid date',doppler_lidar_fleet_valid_date('2026-02-30'),false);
t('bad format',doppler_lidar_fleet_valid_date("2026-1-1' OR 1=1"),false);
exit($fail?1:0);
