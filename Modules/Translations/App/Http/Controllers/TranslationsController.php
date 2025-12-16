<?php

namespace Modules\Translations\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Translations\App\Http\Repository\TranslationRepository;
use Modules\Translations\App\Http\Requests\StoreTranslationRequest;
use Modules\Translations\App\Http\Requests\UpdateSystemRequest;
use Modules\Translations\App\Http\Requests\UpdateTranslationRequest;
use Modules\Translations\App\Model\SystemMessage;
use Modules\Translations\App\Model\SystemMessageTranslation;

class TranslationsController extends Controller
{

    /**
     * TranslationsController constructor.
     *
     * @param TranslationRepository $translationRepository
     */
    public function __construct(public TranslationRepository $translationRepository)
    {
    }

    /**
     * Display a listing of system messages with translations for admin panel.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function adminIndex(Request $request): JsonResponse
    {
        return $this->translationRepository->adminIndex($request);
    }

    /**
     * Store a newly created system message and its translations.
     *
     * Example request body:
     * {
     *   "message": "site_error",
     *   "category": "error",
     *   "translations": {
     *     "uz": "foydalanuvchi topilmadi",
     *     "ru": "Пользователь не найден",
     *     "en": "user not found"
     *   }
     * }
     *
     * Required fields:
     * - message: unique system key (string)
     * - translations: associative array where keys are language codes (e.g., 'uz', 'ru', 'en')
     *   and values are translated texts.
     *
     * @param StoreTranslationRequest $request
     * @return JsonResponse
     */
    public function store(StoreTranslationRequest $request): JsonResponse
    {
        return $this->translationRepository->store($request);
    }
    /**
     * Update an existing system message and its translations.
     *
     * Example request body:
     * {
     *   "message": "site_error",
     *   "category": "error",
     *   "translations": {
     *     "uz": "Foydalanuvchi topilmadi",
     *     "ru": "Пользователь не найден",
     *     "en": "User not found"
     *   }
     * }
     *
     * Fields:
     * - message: (string) Unique system key (optional if not changing)
     * - category: (string) Message category, e.g., 'error', 'info', etc.
     * - translations: (object) Key-value pairs of language codes and their translations
     *
     * @param UpdateSystemRequest $request
     * @param SystemMessage $message
     * @return JsonResponse
     */

    public function update(UpdateSystemRequest $request, SystemMessage $message): JsonResponse
    {
        return $this->translationRepository->update($request, $message);
    }



    /**
     * Show a specific system message and its translations.
     *
     * @param Request $request
     * @param SystemMessage $message
     * @return JsonResponse
     */
    public function show(Request $request, SystemMessage $message): JsonResponse
    {
        return $this->translationRepository->show($request, $message);
    }



    public function list(Request $request): JsonResponse
    {
        return $this->translationRepository->list($request);
    }



}
