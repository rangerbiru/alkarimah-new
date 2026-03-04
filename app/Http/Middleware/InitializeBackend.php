<?php

namespace App\Http\Middleware;

use App\Enums\TransactionFlag;
use App\Enums\UserRole;
use App\Models\CashDeposit;
use App\Models\Menu;
use App\Models\SavingsWithdrawal;
use App\Models\Transaction;
use App\Models\UniqueCodeDeposit;
use App\Models\UserRights;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class InitializeBackend
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $data = [
            'user' => (object) [
                'role' => (object) [
                    'SuperAdmin' => UserRole::SuperAdmin,
                    'OrangTua' => UserRole::OrangTua,
                ]
            ],
            'sidebar' => [
                'none' => [],
                'academic' => [],
                'finance' => [],
            ],
            'header' => []
        ];

        // $sidebar_parent_none = UserRights::whereIdUser($user->id)
        //     ->with(['menu'])
        //     ->whereHas('menu', function($query) {
        //         $query->sidebar()->parent()->groupNone();
        //     })
        //     ->get();

        // foreach ($sidebar_parent_none as $sp) {
        //     $menu = [
        //         'id' => $sp->menu->id,
        //         'name' => $sp->menu->name,
        //         'icon' => $sp->menu->icon,
        //         'route' => $sp->menu->route,
        //         'child' => []
        //     ];

        //     if ($sp->menu->is_parent) {
        //         $child = Menu::whereIdParent($sp->menu->id)->orderBy('sort')->get();

        //         foreach ($child as $c) {
        //             $menu_child = (object) [
        //                 'id' => $c->id,
        //                 'name' => $c->name,
        //                 'icon' => $c->icon,
        //                 'route' => $c->route,
        //                 'child' => []
        //             ];

        //             if ($c->is_parent) {
        //                 $child2 = Menu::whereIdParent($c->id)->orderBy('sort')->get();

        //                 foreach ($child2 as $c2) {
        //                     array_push($menu_child->child, (object) [
        //                         'id' => $c2->id,
        //                         'name' => $c2->name,
        //                         'icon' => $c2->icon,
        //                         'route' => $c2->route,
        //                         'child' => []
        //                     ]);
        //                 }
        //             }

        //             array_push($menu['child'], $menu_child);
        //         }
        //     }

        //     array_push($data['sidebar']['none'], (object) $menu);
        // }

        // $sidebar_parent_academic = UserRights::whereIdUser($user->id)
        //     ->with(['menu'])
        //     ->whereHas('menu', function($query) {
        //         $query->sidebar()->parent()->groupAcademic();
        //     })
        //     ->get();


        // foreach ($sidebar_parent_academic as $sp) {
        //     $menu = [
        //         'id' => $sp->menu->id,
        //         'name' => $sp->menu->name,
        //         'icon' => $sp->menu->icon,
        //         'route' => $sp->menu->route,
        //         'child' => []
        //     ];

        //     if ($sp->menu->is_parent) {
        //         $child = Menu::whereIdParent($sp->menu->id)->orderBy('sort')->get();

        //         foreach ($child as $c) {
        //             $menu_child = (object) [
        //                 'id' => $c->id,
        //                 'name' => $c->name,
        //                 'icon' => $c->icon,
        //                 'route' => $c->route,
        //                 'child' => []
        //             ];

        //             if ($c->is_parent) {
        //                 $child2 = Menu::whereIdParent($c->id)->orderBy('sort')->get();

        //                 foreach ($child2 as $c2) {
        //                     array_push($menu_child->child, (object) [
        //                         'id' => $c2->id,
        //                         'name' => $c2->name,
        //                         'icon' => $c2->icon,
        //                         'route' => $c2->route,
        //                         'child' => []
        //                     ]);
        //                 }
        //             }

        //             array_push($menu['child'], $menu_child);
        //         }
        //     }

        //     array_push($data['sidebar']['academic'], (object) $menu);
        // }

        // $sidebar_parent_finance = UserRights::whereIdUser($user->id)
        //     ->with(['menu'])
        //     ->whereHas('menu', function($query) {
        //         $query->sidebar()->parent()->groupFinance();
        //     })
        //     ->get();

        // foreach ($sidebar_parent_finance as $sp) {
        //     $menu = [
        //         'id' => $sp->menu->id,
        //         'name' => $sp->menu->name,
        //         'icon' => $sp->menu->icon,
        //         'route' => $sp->menu->route,
        //         'child' => []
        //     ];

        //     if ($sp->menu->is_parent) {
        //         $child = Menu::whereIdParent($sp->menu->id)->orderBy('sort')->get();

        //         foreach ($child as $c) {
        //             $menu_child = (object) [
        //                 'id' => $c->id,
        //                 'name' => $c->name,
        //                 'icon' => $c->icon,
        //                 'route' => $c->route,
        //                 'child' => []
        //             ];

        //             if ($c->is_parent) {
        //                 $child2 = Menu::whereIdParent($c->id)->orderBy('sort')->get();

        //                 foreach ($child2 as $c2) {
        //                     array_push($menu_child->child, (object) [
        //                         'id' => $c2->id,
        //                         'name' => $c2->name,
        //                         'icon' => $c2->icon,
        //                         'route' => $c2->route,
        //                         'child' => []
        //                     ]);
        //                 }
        //             }

        //             array_push($menu['child'], $menu_child);
        //         }
        //     }

        //     array_push($data['sidebar']['finance'], (object) $menu);
        // }

        // $header = UserRights::whereIdUser($user->id)
        //     ->with(['menu'])
        //     ->whereHas('menu', function ($query) {
        //         $query->header()->parent();
        //     })
        //     ->get();

        // foreach ($header as $h) {
        //     $menu = [
        //         'id' => $h->menu->id,
        //         'name' => $h->menu->name,
        //         'icon' => $h->menu->icon,
        //         'route' => $h->menu->route,
        //         'child' => []
        //     ];

        //     if ($h->menu->is_parent) {
        //         $child = Menu::whereIdParent($h->menu->id)->orderBy('sort')->get();

        //         foreach ($child as $c) {
        //             array_push($menu['child'], (object) [
        //                 'id' => $c->id,
        //                 'name' => $c->name,
        //                 'icon' => $c->icon,
        //                 'route' => $c->route,
        //             ]);
        //         }
        //     }

        //     array_push($data['header'], (object) $menu);
        // }

        $data['transaction_pending'] = Transaction::notPaid()->count();
        $data['transaction_cash'] = CashDeposit::waiting()->count();
        // $data['transaction_unique_code'] = UniqueCodeDeposit::waiting()->count();
        $data['transaction_unique_code'] = 0;
        $data['transaction_deposit'] = $data['transaction_cash'] + $data['transaction_unique_code'];
        $data['savings_withdrawal'] = SavingsWithdrawal::notProcessed()->count();

        View::share('data', (object) $data);

        return $next($request);
    }
}
