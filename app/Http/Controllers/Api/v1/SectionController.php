<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Section\AddUserSectionRequest;
use App\Http\Requests\Section\AddUserSectionsRequest;
use App\Http\Requests\Section\SectionStoreRequest;
use App\Http\Resources\User\SectionResource;
use App\Models\Section;
use App\Models\User;

class SectionController extends Controller
{
    public function index()
    {
        $data = SectionResource::collection(Section::get());

        return $this->ok($data);
    }

    public function addUserSections(AddUserSectionsRequest $request)
    {
        $user = User::find($request->userId);
        $sections = $request->sections;

        foreach ($sections as $section) {
            $user->sections()->attach($section['id'], ['is_main' => $section['isMain']]);
        }

        return $this->ok($user);
    }

    public function addUserSection(AddUserSectionRequest $request)
    {
        $user = User::find($request->userId);
        $user->sections()->attach($request->sectionId, ['is_main' => $request->isMain]);

        return $this->ok($user);
    }

    public function store(SectionStoreRequest $request)
    {
        $data = Section::create([
            'name' => $request->name,
        ]);

        return $this->ok(new SectionResource($data));
    }

    public function show(string $id)
    {
        $data = Section::find($id);

        return $this->ok(new SectionResource($data));
    }

    public function update(SectionStoreRequest $request, string $id)
    {
        $data = Section::find($id);
        $data->name = $request->name;

        $data->save();

        return $this->ok(new SectionResource($data));
    }

    public function destroy(string $id)
    {
        $data = Section::find($id);
        $data->delete();

        return $this->ok($data);
    }
}
