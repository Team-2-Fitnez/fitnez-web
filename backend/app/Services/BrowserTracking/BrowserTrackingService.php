<?php
namespace App\Services\BrowserTracking;
use App\Models\User;
use App\Services\BrowserTracking\LeaderElection\LeaderElectionFactory;
class BrowserTrackingService {
    public function __construct(private readonly DeviceTracker $devices, private readonly TabTracker $tabs, private readonly LeaderElectionFactory $factory) {}
    public function heartbeat(array $data, ?User $user): array { $data['context_type']=$this->normalize($data['context_type'] ?? 'normal'); $this->devices->touch($data,$user); $tab=$this->tabs->touch($data,$user); return ['tab_id'=>$tab->tab_id,'device_uuid'=>$tab->device_uuid,'context_type'=>$tab->context_type,'is_leader'=>$tab->is_leader,'last_heartbeat_at'=>optional($tab->last_heartbeat_at)->toISOString()]; }
    public function electLeader(array $data, ?User $user): array { $ctx=$this->normalize($data['context_type'] ?? 'normal'); $tab=$this->factory->make($ctx)->elect($user,$data['device_uuid'],$data['tab_id'],$ctx); return ['leader_tab_id'=>$tab->tab_id,'current_tab_id'=>$data['tab_id'],'is_current_tab_leader'=>$tab->tab_id === $data['tab_id'],'context_type'=>$ctx]; }
    public function releaseLeader(array $data): void { $this->tabs->releaseLeader($data['device_uuid'],$data['tab_id'],$this->normalize($data['context_type'] ?? 'normal')); }
    private function normalize(string $context): string { return in_array($context,['private','incognito','ignore'],true) ? 'private' : 'normal'; }
}
