<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\v1\DocumentController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Archive\ArchiveGetFilterRequest;
use App\Http\Requests\Archive\ArchiveStoreRequest;
use App\Http\Resources\Archive\ArchiveResource;
use App\Http\Resources\Archive\ArchiveResourceCollection;
use App\Http\Resources\Document\DocumentResource;
use App\Models\Archive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ArchiveController extends Controller
{
    public function index($limit = 10)
    {
        $data = Archive::orderBy('id', 'desc')->paginate($limit);

        return $this->ok(new ArchiveResourceCollection($data));
    }

    public function test()
    {
        return Auth::user()->hasAnyPermission(['Administrator', 'Super-Admin']);
    }

    public function filter(ArchiveGetFilterRequest $request)
    {
        $data = Archive::orderBy('id', 'desc');
        $filter_bill = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        if (!$request->isNotFilled('way') && $request->way != '') {
            $filter_bill[] = ['way', 'like', '%' . $request->way . '%'];
        }
        if (!$request->isNotFilled('isIn') && $request->isIn != -1) {
            $filter_bill[] = ['is_in', $request->isIn];
        }
        if (!$request->isNotFilled('archiveTypes') && $request->archiveTypes != '') {
            $filter_bill[] = ['archive_type_id', $request->archiveTypeId];
        }
        if (!$request->isNotFilled('archiveTypeId') && $request->archiveTypeId != -1) {
            $filter_bill[] = ['archive_type_id', $request->archiveTypeId];
        }

        if (!$request->isNotFilled('number') && $request->number != '') {
            $data = $data->orWhere('number', 'like', '%' . $request->number . '%');
        }
        if (!$request->isNotFilled('number') && $request->number != '') {
            $data = $data->orWhere('title', 'like', '%' . $request->number . '%');
        }
        if (!$request->isNotFilled('title') && $request->title != '') {
            $data = $data->orWhere('title', 'like', '%' . $request->title . '%');
        }
        if (!$request->isNotFilled('description') && $request->description != '') {
            $filter_bill[] = ['title', 'like', '%' . $request->description . '%'];
        }
        if (
            !$request->isNotFilled('hasDate') && $request->hasDate == 'true' &&
            !$request->isNotFilled('issueDateFrom') && $request->issueDateFrom != '' &&
            !$request->isNotFilled('issueDateTo') && $request->issueDateTo != ''
        ) {
            $data = $data->whereBetween('issue_date', [$request->issueDateFrom, $request->issueDateTo]);
        }
        $data = $data->where($filter_bill);
        if (Auth::user()->hasAnyPermission(['Administrator', 'Super-Admin', 'ViewAllSections'])) {
            if (!$request->isNotFilled('sectionId') && $request->sectionId != '-1') {
                $data = $data->whereRelation('ArchiveType.Section', 'id', '=', Auth::user()->sections()->first()->id);
            }
        } else {
            $data = $data->whereRelation('ArchiveType.Section', 'id', '=', Auth::user()->sections()->first()->id);
        }
        $data = $data->where($filter_bill)->paginate($limit);
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new ArchiveResourceCollection($data));
        }
    }

    public function store(ArchiveStoreRequest $request)
    {
        $this->authorize('create', Archive::class);
        
        $data = Archive::create([
            'title' => $request->title,
            'issue_date' => $request->issue_date,
            'number' => $request->number,
            'way' => $request->way,
            'description' => $request->description,
            'is_in' => $request->is_in,
            'user_id' => Auth::user()->id,
            'user_create_id' => Auth::user()->id,
            'user_update_id' => Auth::user()->id,
            'archive_type_id' => $request->archive_type_id,
        ]);
        if ($request->hasfile('FilesDocument')) {
            $document = new DocumentController();
            $document->store_multi(
                request: $request,
                documentable_id: $data->id,
                documentable_type: Archive::class,
                pathFolder: "archives"
            );
        }
        // foreach ($request->file('Files') as $file) {
        //     $document = new DocumentController();
        //     $document->store(new Request(["files" => $file, "archive_id" => $data->id]));
        // }

        return $this->ok(new ArchiveResource(Archive::find($data->id)));
    }

    public function show(string $id)
    {
        $data = Archive::find($id);

        return $this->ok(new ArchiveResource($data));
    }

    public function show_documents(string $archive_id)
    {
        $data = Archive::find($archive_id);

        return $this->ok(DocumentResource::collection($data->Documents));
    }

    public function update(Request $request, string $id)
    {
        $archive = $this->archiveService->getArchiveById((int)$id);
        
        if ($archive) {
            $this->authorize('update', $archive);
            $archive->title = $request->title;
            $archive->issue_date = $request->issue_date;
            $archive->number = $request->number;
            $archive->way = $request->way;
            $archive->description = $request->description;
            $archive->is_in = $request->is_in;
            $archive->user_id = Auth::user()->id;
            $archive->archive_type_id = $request->archive_type_id;
            $archive->save();
             if ($request->hasfile('FilesDocument')) {
                $document = new DocumentController();
                $document->store_multi(
                    request: $request,
                    documentable_id: $archive->id,
                    documentable_type: Archive::class,
                    pathFolder: "archives"
                );
            }
            return $this->ok(new ArchiveResource($archive));
        }
        return $this->ok(new ArchiveResource([]));
    }

    public function destroy(string $id)
    {
        $archive = $this->archiveService->getArchiveById((int)$id);
        
        if ($archive) {
            $this->authorize('delete', $archive);
            $archive->documents()->delete();
            $archive->delete();
        }

        return $this->ok(null);
    }
}
