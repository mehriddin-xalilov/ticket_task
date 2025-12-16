<?php

namespace Modules\Translations\App\Http\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\Traits\QueryBuilderTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Translations\App\Model\Langs;
use Modules\Translations\App\Model\SystemMessage;
use Modules\Translations\App\Model\SystemMessageTranslation;
use Nwidart\Modules\Collection;
use Illuminate\Pagination\Paginator;

class TranslationRepository
{
    use QueryBuilderTrait;

    /**
     * @var SystemMessage $ modelClass
     */
    protected mixed $modelClass = SystemMessage::class;

    public function adminIndex(Request $request)
    {
        $query = $this->defaultQuery($request);
        $this->defaultAllowFilter($query, $request);
        return $this->withPagination($query, $request);
    }

    public function show(Request $request, SystemMessage $message): JsonResponse
    {
        $this->allowIncludeAndAppend($request, $message);
        return okResponse($message);
    }

    public function store(Request $request): JsonResponse
    {
        $model = SystemMessage::create([
            'message' => $request->message,
            'category' => $request->category ?? 'general'
        ]);
        foreach ($request->translations as $lang=>$translation) {
            SystemMessageTranslation::create([
                'id'=>$model->id,
                'language'=>$lang,
                'translation'=>$translation,
            ]);
        }
        $this->allowIncludeAndAppend($request, $model);
        SystemMessageTranslation::generateJs();
        return okResponse($model);
    }

    public function update(Request $request, SystemMessage $message): JsonResponse
    {
        $message->update($request->only(['message', 'category']));
        if ($request->filled('translations')) {
            foreach ($request->input('translations') as $lang => $translatedText) {
                DB::table('system_message_translation')->updateOrInsert(
                    ['id' => $message->id, 'language' => $lang],
                    ['translation' => $translatedText, 'updated_at' => now()]
                );
            }
        }
        $this->allowIncludeAndAppend($request, $message);
        SystemMessageTranslation::generateJs();
        return okResponse($message);
    }



    public function list(Request $request): JsonResponse
    {
        $message = $request->message;
        if (!empty($message)) {
            $sourses = SystemMessage::where('message', 'LIKE', '%' . $message . '%')
                ->orderBy('id', 'DESC')
                ->get();
        } else {
            $sourses = SystemMessage::orderBy('id', 'DESC')->get();
        }

        $data = [];

        foreach ($sourses as $key => $sours) {
            $langs = Langs::where(['status' => 1])->get();
            $data_lang = [];

            foreach ($langs as $lang) {
                $model = SystemMessageTranslation::where([
                    'id' => $sours->id,
                    'language' => $lang->code
                ])->first();
                $data_lang[$lang->code] = $model ? $model->translation : '';
            }

            $data[$key] = [
                'id' => $sours->id,
                'message' => $sours->message,
                'translations' => $data_lang
            ];
        }

        $paginated = $this->paginate($data, 10);

        return response()->json($paginated);
    }

    public function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            $options
        );
    }


}

