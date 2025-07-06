<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\CardType;
use App\Models\Location;
use App\Models\HousingType;
use App\Models\SocialState;
use App\Models\LevelState;
use App\Models\FamilyMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Imports\PeopleImport;
use Maatwebsite\Excel\Facades\Excel;

 use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rule;
use App\Services\FilterOptionsService;
use App\Http\Resources\PersonResource;


class PersonController extends Controller
{
 



    
        public function index()
    {
     
        $people = Person::with(['cardType', 'housingType', 'location','socialState', 'levelState', 'familyMembers'])->whereIn('location_id', auth()->user()->locations->pluck('id'))
                        ->select([
                            'id', 'name', 'is_male', 'is_beneficiary', 'birth_date',
                            'card_type_id', 'card_number', 'phone_number', 'job',
                            'housing_type_id', 'housing_address', 'location_id',
                            'social_state_id','level_state_id',
                            'meal_count', 'male_count', 'female_count', 'notes',
                            'created_at', 
                        ])
                        ->get();


        return Inertia::render('people/Index', [
            'people' => fn () => $people,
        ]);
    }

    
    
    public function create()
    {
        // جلب البيانات التي ستحتاجها حقول الـ Select
        $cardTypes = CardType::all(['id', 'name']);
        $housingTypes = HousingType::all(['id', 'name']);
        $socialStates = SocialState::all(['id', 'name']);
        $levelStates = LevelState::all(['id', 'name']);
        $userLocations = auth()->user()->locations->pluck('id');
        $locations = Location::select('id', 'name')->whereIn('id', $userLocations)->get();

        // إرجاع البيانات إلى واجهة React باستخدام Inertia
        return inertia('People/Create', [
            'cardTypes' => $cardTypes,
            'locations' => $locations,
            'housingTypes' => $housingTypes,
            'socialStates' => $socialStates,
            'levelStates' => $levelStates,

        ]);
    }

    public function store(Request $request)
    {
        // التحقق من صحة البيانات الأساسية للشخص
        $validatedPersonData = $request->validate([
            'name' => 'required|string|max:255',
            'is_male' => 'required|boolean',
            'is_beneficiary' => 'required|boolean',
            'birth_date' => 'nullable|date',
            'card_type_id' => 'nullable|exists:card_types,id',
            'card_number' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'job' => 'nullable|string|max:255',
            'housing_type_id' => 'nullable|exists:housing_types,id',
            'housing_address' => 'nullable|string|max:255',
            'location_id' => 'nullable|exists:locations,id',
            'social_state_id' => 'nullable|exists:social_states,id',
            'level_state_id' => 'nullable|exists:level_states,id',
            'meal_count' => 'required|integer|min:0',
            'male_count' => 'required|integer|min:0',
            'female_count' => 'required|integer|min:0',
            'notes' => 'nullable|string',
            // التحقق من صحة بيانات أفراد الأسرة (إذا كانت موجودة)
            'family_members' => 'nullable|array',
            'family_members.*.birth_date' => 'required|date',
            'family_members.*.is_male' => 'required|boolean',
        ]);

                    
        // التأكد أن المستخدم مخول لإضافة لهذه الحارة
        if (!auth()->user()->locations->contains($request->location_id)) {
            return redirect()->back()->withErrors(['msg' => 'ليس لديك صلاحية لإضافة طالب للحارة المستهدفة.'])->withInput();
        }


        DB::transaction(function () use ($validatedPersonData) {
            // إنشاء سجل الشخص الجديد
            $person = Person::create($validatedPersonData);

            // إذا كان هناك أفراد أسرة، قم بإضافتهم
            if (isset($validatedPersonData['family_members'])) {
                foreach ($validatedPersonData['family_members'] as $memberData) {
                    $person->familyMembers()->create($memberData);
                }
            }
        });

        // رسالة نجاح وإعادة توجيه
        return redirect()->route('people.report.view')->with('success', 'تم إضافة الشخص بنجاح.');
    }
    
    
public function show(Person $person)
{
    $person->load([
        'cardType',
        'housingType',
        'location',
        'socialState',
        'levelState',
        'familyMembers',
    ]);

    return Inertia::render('People/Show', [
        'person' => $person,
    ]);
}



public function import(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls'
    ]);

    Excel::import(new PeopleImport, $request->file('file'));

    return redirect()->route('people.index')->with('success', 'تم استيراد البيانات بنجاح!');
}
    
    
    public function edit(Person $person)
    {
        // تحميل العلاقات الضرورية لعرضها في النموذج
        $person->load(['cardType', 'housingType','socialState','levelState', 'location', 'familyMembers']);

        $cardTypes = CardType::all(['id', 'name']);
        $housingTypes = HousingType::all(['id', 'name']);
        $socialStates = SocialState::all(['id', 'name']);
        $levelStates = LevelState::all(['id', 'name']);
        $userLocations = auth()->user()->locations->pluck('id');
        $locations = Location::select('id', 'name')->whereIn('id', $userLocations)->get();

        return inertia('People/Edit', [
            'person' => $person,
            'cardTypes' => $cardTypes,
            'locations' => $locations,
            'housingTypes' => $housingTypes,
            'socialStates' => $socialStates,
            'levelStates' => $levelStates,

        ]);
    }


    public function update(Request $request, Person $person)
    {
        

        // حماية التحديث حسب صلاحية الحارة المستهدفة
        if (!auth()->user()->locations->contains($person->location_id)) {
            abort(403, 'ليس لديك صلاحية لتعديل البيانات في هذه الحارة.');
        }
        $validatedPersonData = $request->validate([
            'name' => 'required|string|max:255',
            'is_male' => 'required|boolean',
            'is_beneficiary' => 'required|boolean',
            'birth_date' => 'nullable|date',
            'card_type_id' => 'nullable|exists:card_types,id',
            'card_number' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'job' => 'nullable|string|max:255',
            'housing_type_id' => 'nullable|exists:housing_types,id',
            'housing_address' => 'nullable|string|max:255',
            'location_id' => 'nullable|exists:locations,id',
            'social_state_id' => 'nullable|exists:social_states,id',
            'level_state_id' => 'nullable|exists:level_states,id',
            'male_count' => 'required|integer|min:0',
            'meal_count' => 'required|integer|min:0',
            'female_count' => 'required|integer|min:0',
            'notes' => 'nullable|string',
            // التحقق من صحة بيانات أفراد الأسرة
            'family_members' => 'nullable|array',
            'family_members.*.id' => ['nullable', 'integer', Rule::exists('family_members', 'id')->where(function ($query) use ($person) {
                $query->where('person_id', $person->id);
            })],
            'family_members.*.birth_date' => 'required|date',
            'family_members.*.is_male' => 'required|boolean',
        ]);

        DB::transaction(function () use ($request, $person, $validatedPersonData) {
            // تحديث بيانات الشخص الأساسية
            $person->update($validatedPersonData);

            // معالجة أفراد الأسرة
            $existingFamilyMemberIds = $person->familyMembers->pluck('id')->toArray();
            $incomingFamilyMemberIds = [];

            if (isset($validatedPersonData['family_members'])) {
                foreach ($validatedPersonData['family_members'] as $memberData) {
                    if (isset($memberData['id'])) {
                        // تحديث عضو موجود
                        $familyMember = FamilyMember::find($memberData['id']);
                        if ($familyMember && $familyMember->person_id === $person->id) { // تأكد من أن العضو تابع لهذا الشخص
                            $familyMember->update($memberData);
                            $incomingFamilyMemberIds[] = $memberData['id'];
                        }
                    } else {
                        // إضافة عضو جديد
                        $person->familyMembers()->create($memberData);
                    }
                }
            }

            // حذف أفراد الأسرة الذين تم حذفهم من النموذج
            $membersToDelete = array_diff($existingFamilyMemberIds, $incomingFamilyMemberIds);
            if (!empty($membersToDelete)) {
                FamilyMember::whereIn('id', $membersToDelete)->delete();
            }
        });

return redirect()->route('people.show', $person)->with('success', 'تم تحديث بيانات الشخص بنجاح.');
    }


    public function destroy(Person $person)
    {
        
    if (!auth()->user()->locations->contains($person->location_id)) {
        abort(403, 'ليس لديك صلاحية لحذف  البيانات.');
    }
        $person->delete(); // بما أن العلاقة familyMembers تستخدم cascadeOnDelete، سيتم حذف الأفراد تلقائياً
        return redirect()->route('people.report.view')->with('success', 'تم الحذف  بنجاح.');
;
    }



    const DEFAULT_PER_PAGE = 100;

    public function searchForm()
    {
        $options = FilterOptionsService::get();

        return inertia('People/PeopleSearchPage', [
            ...$options,
            'people' => [],
            'pagination' => null,
            'searchParams' => [
                'search_name' => null,
                'card_type_id' => [], 
                'housing_type_id' => [],
                'location_id' => [],
                'social_state_id' => [],
                'level_state_id' => [],
                'is_male' => null,
                'is_beneficiary' => null,
                'page' => 1,
                'sort_by' => null,
                'sort_direction' => null,
            ]
        ]);
    }

    public function getSearchResults(Request $request)
    {
        $validated = $request->validate([
            'search_name' => 'nullable|string|max:255',
            'card_type_id' => 'nullable|array',
            'card_type_id.*' => 'integer|exists:card_types,id',
            'housing_type_id' => 'nullable|array',
            'housing_type_id.*' => 'integer|exists:housing_types,id',
            'location_id' => 'nullable|array',
            'location_id.*' => 'integer|exists:locations,id',
            'social_state_id' => 'nullable|array',
            'social_state_id.*' => 'integer|exists:social_states,id',
            'level_state_id' => 'nullable|array',
            'level_state_id.*' => 'integer|exists:level_states,id',
            'is_male' => 'nullable|boolean',
            'is_beneficiary' => 'nullable|boolean',
            'sort_by' => 'nullable|string',
            'sort_direction' => 'nullable|in:asc,desc',
        ]);

        $query = Person::with([
            'cardType', 'housingType', 'location', 
            'socialState', 'levelState'
        ])->withCount('familyMembers');

        // تصفية البحث بشكل ديناميكي
        $filters = [
            'card_type_id',
            'housing_type_id',
            'location_id',
            'social_state_id',
            'level_state_id'
        ];

        foreach ($filters as $filter) {
            if (!empty($validated[$filter])) {
                $query->whereIn($filter, $validated[$filter]);
            }
        }

        if (!empty($validated['search_name'])) {
            $query->where('name', 'like', '%' . $validated['search_name'] . '%');
        }

        if (isset($validated['is_male'])) {
            $query->where('is_male', $validated['is_male']);
        }

        if (isset($validated['is_beneficiary'])) {
            $query->where('is_beneficiary', $validated['is_beneficiary']);
        }

        // ترتيب البيانات
        $allowedSort = ['id', 'name', 'birth_date'];
        $sortBy = $validated['sort_by'] ?? 'id';
        $sortDirection = $validated['sort_direction'] ?? 'desc';

        if (in_array($sortBy, $allowedSort)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $pagination = $query->paginate(self::DEFAULT_PER_PAGE);

        return inertia('People/PeopleSearchPage', [
            ...FilterOptionsService::get(),
            'people' => PersonResource::collection($pagination->items()),
            'pagination' => $pagination->toArray(),
            'searchParams' => $validated
        ]);
    }

    
    
}








