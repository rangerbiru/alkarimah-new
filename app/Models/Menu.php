<?php

namespace App\Models;

use App\Casts\Json;
use App\Enums\GroupMenu;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menu';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'route' => Json::class,
            'group' => GroupMenu::class,
            'actions' => Json::class,
            'is_parent' => 'boolean',
            'is_sidebar' => 'boolean',
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->whereId(Crypt::decrypt($value))->firstOrFail();
    }

    public function scopeParent($query)
    {
        return $query->whereNull('id_parent');
    }

    public function scopeSidebar($query)
    {
        return $query->whereIsSidebar(true);
    }

    public function scopeHeader($query)
    {
        return $query->whereIsSidebar(false);
    }

    public function scopeGroupNone($query)
    {
        return $query->whereGroup(GroupMenu::None);
    }

    public function scopeGroupAcademic($query)
    {
        return $query->whereGroup(GroupMenu::Akademik);
    }

    public function scopeGroupFinance($query)
    {
        return $query->whereGroup(GroupMenu::Keuangan);
    }
}
