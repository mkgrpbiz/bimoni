<?php

namespace Database\Seeders;

use App\Models\FormField;
use Illuminate\Database\Seeder;

class FormFieldSeeder extends Seeder
{
    public function run(): void
    {
        $fields = [
            [
                'field_key' => 'name', 'label' => '名前', 'type' => 'text',
                'is_required' => true, 'is_system' => true, 'maps_to' => 'name', 'sort_order' => 1,
            ],
            [
                'field_key' => 'name_kana', 'label' => 'フリガナ', 'type' => 'text',
                'is_required' => true, 'is_system' => true, 'maps_to' => 'name_kana', 'sort_order' => 2,
            ],
            [
                'field_key' => 'birthdate', 'label' => '生年月日', 'type' => 'date',
                'is_required' => false, 'is_system' => true, 'maps_to' => 'birthdate', 'sort_order' => 3,
            ],
            [
                'field_key' => 'gender', 'label' => '性別', 'type' => 'radio',
                'is_required' => false, 'is_system' => true, 'maps_to' => 'gender', 'sort_order' => 4,
                'options' => [
                    ['value' => 'female', 'label' => '女性'],
                    ['value' => 'male',   'label' => '男性'],
                    ['value' => 'other',  'label' => 'その他'],
                ],
            ],
            [
                'field_key' => 'available_times', 'label' => '実施可能な時間帯', 'type' => 'checkbox',
                'is_required' => false, 'is_system' => true, 'maps_to' => 'available_times', 'sort_order' => 5,
                'options' => [
                    ['value' => '10:00〜13:00', 'label' => '10:00〜13:00'],
                    ['value' => '14:00〜17:00', 'label' => '14:00〜17:00'],
                    ['value' => '18:00〜20:00', 'label' => '18:00〜20:00'],
                    ['value' => '21:00〜24:00', 'label' => '21:00〜24:00'],
                    ['value' => 'いつでもOK',   'label' => 'いつでもOK'],
                ],
            ],
            [
                'field_key' => 'wants_continuation', 'label' => '継続希望', 'type' => 'radio',
                'is_required' => false, 'is_system' => true, 'maps_to' => 'wants_continuation', 'sort_order' => 6,
                'options' => [
                    ['value' => '1', 'label' => '継続希望する'],
                    ['value' => '0', 'label' => '継続不要'],
                ],
            ],
            [
                'field_key' => 'phone', 'label' => '電話番号', 'type' => 'tel',
                'is_required' => false, 'is_system' => true, 'maps_to' => 'phone', 'sort_order' => 7,
            ],
            [
                'field_key' => 'email', 'label' => 'メールアドレス', 'type' => 'email',
                'is_required' => false, 'is_system' => true, 'maps_to' => 'email', 'sort_order' => 8,
            ],
        ];

        foreach ($fields as $field) {
            FormField::firstOrCreate(['field_key' => $field['field_key']], array_merge($field, ['is_visible' => true]));
        }
    }
}
