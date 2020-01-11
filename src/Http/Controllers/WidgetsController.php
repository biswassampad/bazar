<?php

namespace Bazar\Http\Controllers;

use Bazar\Contracts\Models\User;
use Bazar\Models\Order;
use Bazar\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class WidgetsController extends Controller
{
    /**
     * Show the activities.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function activities(): JsonResponse
    {
        $orders = Cache::remember('bazar.activities', 3600, function () {
            return Order::latest()->take(3)->get()->map(function ($order) {
                return [
                    'icon' => 'shop-basket',
                    'url' => route('bazar.orders.show', $order),
                    'title' => __('Order #:id', ['id' => $order->id]),
                    'description' => __('A new order was placed'),
                    'created_at' => $order->created_at->toAtomString(),
                    'formatted_created_at' => $order->created_at->diffForHumans(),
                ];
            });
        });

        return Response::json($orders);
    }

    /**
     * Show the metrics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function metrics(): JsonResponse
    {
        $metrics = Cache::remember('bazar.metrics', 3600, function () {
            return [
                'orders' => Order::count(),
                'products' => Product::count(),
                'users' => app(User::class)->newQuery()->count(),
            ];
        });

        return Response::json($metrics);
    }

    /**
     * Show the sales.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sales(): JsonResponse
    {
        $sales = Cache::remember('bazar.sales', 3600, function () {
            $days = array_reverse(array_reduce(array_fill(0, 7, null), function ($days, $day) {
                return array_merge($days, [Carbon::now()->subDays(count($days))->format('m-d')]);
            }, []));

            $orders = Order::whereNotIn('status', ['cancelled', 'failed'])->where(
                'created_at', '>=', Carbon::now()->subDays(7)->startOfDay()
            )->get()->groupBy(function ($order) {
                return $order->created_at->format('m-d');
            })->map(function ($group) {
                return $group->count();
            })->toArray();

            return [
                'labels' => $days,
                'data' => array_values(array_replace(array_fill_keys($days, 0), $orders)),
            ];
        });

        return Response::json($sales);
    }
}
