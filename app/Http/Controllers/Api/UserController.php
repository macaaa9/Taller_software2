<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkStoreUsersRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private const DEFAULT_PER_PAGE = 50;
    private const MAX_PER_PAGE = 100;

    /** Lee y limita page / per_page del query string. */
    private function params(Request $request): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);
        $perPage = min(max(1, $perPage), self::MAX_PER_PAGE);

        return [$page, $perPage];
    }

    /** Respuesta con total + data (como pide el taller) y metadatos. */
    private function respond($paginator, array $extra = []): JsonResponse
    {
        return response()->json($extra + [
            'total'     => $paginator->total(),
            'page'      => $paginator->currentPage(),
            'per_page'  => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'data'      => $paginator->items(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        [$page, $perPage] = $this->params($request);

        $p = User::query()->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->respond($p);
    }

    public function emails(Request $request): JsonResponse
    {
        [$page, $perPage] = $this->params($request);

        $p = User::query()->orderBy('id')
            ->paginate($perPage, ['id', 'email'], 'page', $page);

        return $this->respond($p);
    }

    public function overTwenty(Request $request): JsonResponse
    {
        [$page, $perPage] = $this->params($request);
        $cutoff = Carbon::now()->subYears(20)->startOfDay();

        $p = User::query()
            ->where('birth_date', '<=', $cutoff->toDateString())
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->respond($p, ['cutoff_date' => $cutoff->toDateString()]);
    }

    public function bulkStore(BulkStoreUsersRequest $request): JsonResponse
    {
        $created = [];

        foreach ($request->validated()['users'] as $userData) {
            $created[] = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'birth_date' => $userData['birth_date'],
                'password' => Hash::make($userData['password'] ?? 'password'),
            ]);
        }

        return response()->json([
            'message' => 'Se crearon 3 usuarios correctamente.',
            'users' => $created,
        ], 201);
    }
}