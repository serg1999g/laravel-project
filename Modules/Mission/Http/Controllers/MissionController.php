<?php

namespace Modules\Mission\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Modules\Mission\Http\Requests\MissionRequest;
use Modules\Mission\Http\Resources\MissionResource;
use Modules\Mission\Models\Mission;
use PHPUnit\Util\Json;


class MissionController extends BaseController
{
    /**
     *  All missions
     *
     * @return JsonResponse
     */
    public function index()
    {
        if (Auth::check()) {
            $missions = Mission::all();
            $response = MissionResource::collection($missions);

            return $this->respondWithArray(['data' => $response]);
        } else {
            return $this->sendError(__('messages.unsuccessfulOperation'));
        }
    }

    /**
     *  Mission creation
     *
     * @param MissionRequest $request
     * @return JsonResponse
     */
    public function create(MissionRequest $request)
    {
        $user = $request->user();

        $response = $user->missions()->create([
            'name'          => $request->input('name'),
            'description'   => $request->input('description'),
            'content'       => $request->input('content'),
            'location'      => $request->input('location'),
            'language'      => $request->input('language'),
            'duration'      => $request->input('duration'),
            'start'         => $request->input('start'),
        ]);

        for ($i = 0; $i <= 5; $i++) {
            $image = $request->file('image-' . $i);;
            if (!isset($image)) {
                break;
            }
            if ($image !== null && isset($image)) {
                $imageName = rand() . $image->getClientOriginalName();
                $image->move(public_path('storage'), $imageName);
                $response->images()->create(['image' => $imageName]);
            }
        }

        $result = MissionResource::make($response);

        return $this->respondWithArray(['data' => $result]);
    }

    /**
     * Show mission
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id)
    {
        $mission = Mission::find($id);
        $response = MissionResource::make($mission);

        return $this->respondWithArray(['data' => $response]);
    }

    /**
     * Edit mission if you are its creator
     *
     * @param int $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function edit(int $id)
    {
        $mission = Mission::findOrFail($id);
        $this->authorize('edit', $mission);
        $response = MissionResource::make($mission);

        return $this->sendResponse($response, __('messages.successfulOperation'));
    }

    /**
     * Update mission if you are its creator
     *
     * @param MissionRequest $request
     * @param int $id
     * @return Json
     * @throws AuthorizationException
     */
    public function update(MissionRequest $request, int $id)
    {
        $mission = Mission::findOrFail($id);
        $this->authorize('update', $mission);

        $mission->fill($request->all());
        $mission->save();

        for ($i = 0; $i <= 5; $i++) {
            $image = $request->file('image-' . $i);
            if (!isset($image)) {
                continue;
            }
            if ($image !== null && isset($image)) {
                $imageName = rand() . $image->getClientOriginalName();
                $image->move(public_path('storage'), $imageName);
                $mission->images()->create(['image' => $imageName]);
            }
        }

        $response = MissionResource::make($mission);

        return $this->respondWithArray(['data' => $response]);
    }

    /**
     * Delete mission if you are its creator
     *
     * @param int $id
     * @return Json
     * @throws AuthorizationException
     * @throws Exception
     */
    public function destroy(int $id)
    {
        $mission = Mission::findOrFail($id);
        $this->authorize('delete', $mission);
        $mission->delete();

        return $this->respondWithMessage(__('messages.successfulOperation'));
    }
}
