<?php
namespace App\Support;
use Illuminate\Http\JsonResponse;
class ApiResponseBuilder {
    private bool $success = true; private string $message = 'OK'; private mixed $data = null; private int $status = 200; private array $errors = [];
    public static function make(): self { return new self(); }
    public function success(bool $success = true): self { $this->success=$success; return $this; }
    public function message(string $message): self { $this->message=$message; return $this; }
    public function data(mixed $data): self { $this->data=$data; return $this; }
    public function errors(array $errors): self { $this->errors=$errors; return $this; }
    public function status(int $status): self { $this->status=$status; return $this; }
    public function build(): JsonResponse { $p=['success'=>$this->success,'message'=>$this->message,'data'=>$this->data]; if($this->errors) $p['errors']=$this->errors; return response()->json($p,$this->status); }
}
