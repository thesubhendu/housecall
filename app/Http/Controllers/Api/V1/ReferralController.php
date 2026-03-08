<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CancelReferralAction;
use App\Actions\CreateReferralAction;
use App\Data\CancelReferralData;
use App\Data\CreateReferralData;
use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReferralRequest;
use App\Http\Requests\ListReferralsRequest;
use App\Http\Resources\ReferralResource;
use App\Models\Referral;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ReferralController extends Controller
{
    public function index(ListReferralsRequest $request): AnonymousResourceCollection
    {
        $referrals = Referral::query()
            ->when(
                $request->string('status')->isNotEmpty(),
                fn ($query) => $query->where('status', ReferralStatus::from($request->string('status')->toString()))
            )
            ->when(
                $request->string('priority')->isNotEmpty(),
                fn ($query) => $query->where('priority', ReferralPriority::from($request->string('priority')->toString()))
            )
            ->when(
                $request->string('referring_party')->isNotEmpty(),
                fn ($query) => $query->where('referring_party', 'like', '%'.$request->string('referring_party').'%')
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ReferralResource::collection($referrals);
    }

    public function show(Referral $referral): ReferralResource
    {
        return new ReferralResource($referral);
    }

    public function store(CreateReferralRequest $request, CreateReferralAction $action): JsonResponse
    {
        $data = CreateReferralData::fromRequest($request);

        ['referral' => $referral, 'is_duplicate' => $isDuplicate] = $action->execute($data);

        $status = $isDuplicate ? 409 : 201;

        return (new ReferralResource($referral))
            ->response()
            ->setStatusCode($status);
    }

    public function cancel(Request $request, Referral $referral, CancelReferralAction $action): ReferralResource
    {
        $data = new CancelReferralData(
            reason: $request->string('reason')->toString() ?: null,
        );

        $referral = $action->execute($referral, $data);

        return new ReferralResource($referral);
    }
}
