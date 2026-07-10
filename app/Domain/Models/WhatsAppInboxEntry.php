<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro local de cada mensaje de WhatsApp ya procesado. Da idempotencia
 * al pull (un mensaje visto no se re-aplica) y alimenta la lista de
 * "últimos mensajes" en Ajustes.
 */
class WhatsAppInboxEntry extends Model
{
    public const STATUS_APPLIED = 'applied';

    public const STATUS_FAILED = 'failed';

    protected $table = 'whatsapp_inbox';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'transaction_id', 'status', 'reason', 'raw_text'];
}
