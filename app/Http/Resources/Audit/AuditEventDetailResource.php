<?php

namespace App\Http\Resources\Audit;

use App\Models\AuditEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuditEvent
 */
class AuditEventDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'occurred_at' => $this->occurred_at->toIso8601String(),
            'actor' => [
                'type' => $this->actor_type,
                'name' => $this->actor_name,
                'email' => $this->actor_email,
                'label' => $this->actorLabel(),
            ],
            'action' => $this->action,
            'action_label' => __("audit.actions.{$this->action}"),
            'module' => $this->module,
            'module_label' => __("audit.modules.{$this->module}"),
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject_label' => $this->subject_label,
            'description' => $this->description,
            'result' => $this->result,
            'result_label' => __("audit.results.{$this->result}"),
            'before' => $this->before,
            'after' => $this->after,
            'metadata' => $this->metadata,
            'request_id' => $this->request_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
        ];
    }

    private function actorLabel(): string
    {
        if ($this->actor_type === 'system') {
            return (string) __('audit.actors.system');
        }

        if ($this->actor_type === 'anonymous') {
            return (string) __('audit.actors.anonymous');
        }

        if (is_string($this->actor_name) && trim($this->actor_name) !== '') {
            return $this->actor_name;
        }

        return $this->actor_id === null
            ? (string) __('audit.actors.anonymous')
            : 'Pengguna #'.$this->actor_id;
    }
}
