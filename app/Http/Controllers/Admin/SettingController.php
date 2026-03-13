<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SopCategory;
use App\Models\SopDepartment;
use App\Models\SopSourceApp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings.index', [
            'expiryThreshold' => (int) (Setting::getValue('expiry_threshold_days', 30)),
            'categories' => SopCategory::query()
                ->where('active', true)
                ->withCount('documents')
                ->orderBy('name')
                ->get(),
            'departments' => SopDepartment::query()
                ->where('active', true)
                ->withCount('documents')
                ->orderBy('name')
                ->get(),
            'sourceApps' => SopSourceApp::query()
                ->where('active', true)
                ->withCount('documents')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('settingsMain', [
            'expiry_threshold_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'expiry_threshold_days'],
            ['value_json' => $data['expiry_threshold_days']]
        );

        return back()->with('success', 'Settings saved.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        return $this->storeMasterData($request, 'category');
    }

    public function updateCategory(Request $request, SopCategory $category): RedirectResponse
    {
        return $this->updateMasterData($request, $category, 'category');
    }

    public function destroyCategory(SopCategory $category): RedirectResponse
    {
        return $this->deactivateMasterData($category, 'category');
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        return $this->storeMasterData($request, 'department');
    }

    public function updateDepartment(Request $request, SopDepartment $department): RedirectResponse
    {
        return $this->updateMasterData($request, $department, 'department');
    }

    public function destroyDepartment(SopDepartment $department): RedirectResponse
    {
        return $this->deactivateMasterData($department, 'department');
    }

    public function storeSourceApp(Request $request): RedirectResponse
    {
        return $this->storeMasterData($request, 'source_app');
    }

    public function updateSourceApp(Request $request, SopSourceApp $sourceApp): RedirectResponse
    {
        return $this->updateMasterData($request, $sourceApp, 'source_app');
    }

    public function destroySourceApp(SopSourceApp $sourceApp): RedirectResponse
    {
        return $this->deactivateMasterData($sourceApp, 'source_app');
    }

    private function storeMasterData(Request $request, string $type): RedirectResponse
    {
        $config = $this->masterConfig($type);
        $data = $request->validateWithBag($config['create_bag'], [
            'name' => ['required', 'string', 'max:120'],
        ]);

        $name = $this->normalizeName($data['name']);
        if ($name === '') {
            return back()->withErrors(['name' => $config['label'].' is required.'], $config['create_bag']);
        }

        /** @var Model $modelClass */
        $modelClass = $config['model'];
        /** @var Model|null $existing */
        $existing = $modelClass::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->first();

        if ($existing) {
            if (!$existing->active) {
                $existing->forceFill(['active' => true])->save();

                return back()->with('success', $config['label'].' reactivated.');
            }

            return back()->withErrors(['name' => $config['label'].' already exists.'], $config['create_bag']);
        }

        $modelClass::query()->create([
            'name' => $name,
            'active' => true,
        ]);

        return back()->with('success', $config['label'].' added.');
    }

    private function updateMasterData(Request $request, Model $item, string $type): RedirectResponse
    {
        $config = $this->masterConfig($type);
        $data = $request->validateWithBag($config['update_bag'], [
            'name' => ['required', 'string', 'max:120'],
        ]);

        $name = $this->normalizeName($data['name']);
        if ($name === '') {
            return back()->withErrors(['name' => $config['label'].' is required.'], $config['update_bag']);
        }

        /** @var Model $modelClass */
        $modelClass = $config['model'];
        $duplicate = $modelClass::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->where($item->getKeyName(), '!=', $item->getKey())
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['name' => $config['label'].' already exists.'], $config['update_bag']);
        }

        $item->forceFill([
            'name' => $name,
            'active' => true,
        ]);

        if ($item->isDirty()) {
            $item->save();

            return back()->with('success', $config['label'].' updated.');
        }

        return back()->with('success', 'No changes detected.');
    }

    private function deactivateMasterData(Model $item, string $type): RedirectResponse
    {
        $config = $this->masterConfig($type);

        if (!$item->active) {
            return back()->with('success', $config['label'].' already removed.');
        }

        $item->forceFill(['active' => false])->save();

        return back()->with('success', $config['label'].' removed.');
    }

    /**
     * @return array{label:string,model:class-string<Model>,create_bag:string,update_bag:string}
     */
    private function masterConfig(string $type): array
    {
        return match ($type) {
            'category' => [
                'label' => 'Category',
                'model' => SopCategory::class,
                'create_bag' => 'categoryCreate',
                'update_bag' => 'categoryUpdate',
            ],
            'department' => [
                'label' => 'Department',
                'model' => SopDepartment::class,
                'create_bag' => 'departmentCreate',
                'update_bag' => 'departmentUpdate',
            ],
            'source_app' => [
                'label' => 'Source app',
                'model' => SopSourceApp::class,
                'create_bag' => 'sourceAppCreate',
                'update_bag' => 'sourceAppUpdate',
            ],
            default => throw new \InvalidArgumentException('Unknown master data type: '.$type),
        };
    }

    private function normalizeName(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
