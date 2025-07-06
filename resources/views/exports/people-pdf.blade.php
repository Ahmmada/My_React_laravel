<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <style>
    body {
      font-family: 'xbriyaz';
      direction: rtl;
      text-align: right;
      font-size: 14px; /* زيادة حجم الخط */
      margin: 0;
      padding: 0;
      background-color: #f8f8f8; /* خلفية خفيفة */
    }

    .table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px; /* زيادة المسافة */
      border: 1px solid #ddd; /* إطار للجدول */
    }

    thead {
      background-color: #3498db; /* لون أزرق مريح */
      color:  #e5e5e5; 
      font-size: 14px; /* زيادة حجم الخط */
      font-weight: bold; /* خط غامق */
    }

    th {
      border: 1px solid #ccc;
      padding: 10px; /* زيادة التباعد */
      font-size: 14px; /* زيادة حجم الخط */
      background-color: #3498db; /* لون موحد */
      color:  #e5e5e5; /* نص أبيض */
    }

    td {
      border: 1px solid #ccc;
      padding: 10px; /* زيادة التباعد */
      font-size: 14px; /* زيادة حجم الخط */
      background-color: #fff; /* خلفية بيضاء */
    }

    tr:nth-child(even) td {
      background-color: #f9f9f9;
    }

    .header {
      text-align: center;
      font-size: 18px; /* زيادة حجم الخط */
      font-weight: bold; /* خط غامق */
      padding: 15px 0; /* زيادة المسافة */
      border-bottom: 2px solid #3498db; /* إطار أسفل الترويسة */
      background-color: #e7f3fe; /* خلفية خفيفة */
    }

    /* تحسين تصميم التذييل */
    .footer-table {
      width: 100%;
      font-size: 6px; /* زيادة حجم الخط */
      color: #777;
      border-top: 2px solid #3498db; /* إطار أعلى التذييل */
      padding: 10px 0; /* زيادة المسافة */
      border-collapse: collapse;
      background-color: #f8f8f8; /* خلفية خفيفة */
    }

    .footer-table td {
      vertical-align: middle;
      padding: 0 15px; /* زيادة التباعد */
      border: none;
      background-color: #f8f8f8;
      font-size: 6px;
    }

    .footer-page-info {
      text-align: center;
    }
    .footer-created-by {
      text-align: right;
    }
    .footer-date {
      text-align: left;
    }
  </style>
</head>

<body>

{{-- ترويسة --}}
<htmlpageheader name="header1">
  <div class="header">مبادرة إطعام</div>
</htmlpageheader>

{{-- تذييل --}}
<htmlpagefooter name="footer1">
  <table class="footer-table">
    <tr>
      <td class="footer-created-by">تم إنشاء التقرير بواسطة {{ $createdBy }}</td>
      <td class="footer-page-info">صفحة {PAGENO} من {nbpg}</td>
      <td class="footer-date"> بتاريخ {{ now()->format('H:i d-m-Y') }}</td>
    </tr>
  </table>
</htmlpagefooter>

{{-- تفعيل الترويسة والتذييل --}}
<sethtmlpageheader name="header1" value="on" show-this-page="1" />
<sethtmlpagefooter name="footer1" value="on" />

{{-- جدول البيانات --}}
<table class="table">
  <thead>
    <tr>
      @foreach ($columns as $field)
        <th>{{ getFieldLabel($field) }}</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @foreach ($people as $person)
      <tr>
        @foreach ($columns as $field)
          @php
              switch ($field) {
                  case 'card_type.name': $value = $person->cardType?->name ?? ''; break;
                  case 'housing_type.name': $value = $person->housingType?->name ?? ''; break;
                  case 'location.name': $value = $person->location?->name ?? ''; break;
                  case 'social_state.name': $value = $person->socialState?->name ?? ''; break;
                  case 'level_state.name': $value = $person->levelState?->name ?? ''; break;
                  case 'is_male': $value = $person->is_male ? 'ذكر' : 'أنثى'; break;
                  case 'is_beneficiary': $value = $person->is_beneficiary ? 'مستفيد' : 'غير مستفيد'; break;
                  case 'family_members':
                      $value = $person->familyMembers->count() > 0
                          ? $person->familyMembers->count().' أفراد'
                          : 'لا يوجد';
                      break;
                  default: $value = data_get($person, $field);
              }
          @endphp
          <td>{{ $value }}</td>
        @endforeach
      </tr>
    @endforeach
  </tbody>
</table>

</body>
</html>