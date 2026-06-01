return [

    /*
    |--------------------------------------------------------------------------
    | Бизнес-профиль WhatsApp
    |--------------------------------------------------------------------------
    |
    | Поля бизнес-профиля, которые команда bot:profile-sync пушит в Cloud API
    | (POST /{phone_number_id}/whatsapp_business_profile). Отображаемое имя
    | (display name) сюда не входит — оно меняется через ревью Meta.
    |
    */

    'about' => {!! var_export($about, true) !!},

    'description' => {!! var_export($description, true) !!},

    'address' => {!! var_export($address, true) !!},

    'email' => {!! var_export($email, true) !!},

    'vertical' => {!! var_export($vertical, true) !!},

    'websites' => [
@foreach($websites as $website)
        '{{ $website }}',
@endforeach
    ],

];