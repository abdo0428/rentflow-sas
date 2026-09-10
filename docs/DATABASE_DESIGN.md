# Database design

MySQL، محرك InnoDB، ترميز utf8mb4. موديلات المجال تحتوي timestamps وfactories؛ seeders تولّد بيانات Demo.

| الجدول | العلاقات الأساسية |
| --- | --- |
| management_companies | المستخدمون والمباني والوحدات والمستأجرون والسجلات التابعة |
| users | company_id اختياري؛ tenant واحد؛ طلبات صيانة مسندة؛ مستندات مرفوعة |
| buildings | شركة؛ وحدات؛ إعلانات؛ طلبات صيانة |
| units | شركة ومبنى؛ عقود ودفعات وصيانة |
| tenants | شركة؛ user اختياري وفريد؛ عقود ودفعات وصيانة |
| lease_contracts | شركة ومستأجر ووحدة؛ دفعات ومستندات |
| rent_payments | شركة وعقد ومستأجر ووحدة |
| maintenance_requests | شركة ومبنى ووحدة ومستأجر؛ assigned_to وصورة خاصة اختياريان |
| maintenance_activities | شركة وطلب صيانة ومستخدم؛ تغيير حالة أو ملاحظة داخلية |
| notifications | إشعارات Laravel polymorphic، مقيدة بالمستخدم المستلم |
| announcements | شركة؛ مبنى اختياري؛ target يحدد الجمهور داخل الشركة |
| documents | شركة؛ documentable polymorphic؛ uploaded_by |
| audit_logs | شركة ومستخدم اختياريان؛ مرجع موديل؛ old/new JSON |

## القيود

- فريد: slug للشركة، email للمستخدم، (company_id, code) للمبنى، (building_id, unit_number) للوحدة، (company_id, contract_number) للعقد، (lease_contract_id, due_date) للدفعة، user_id للمستأجر.
- مفاتيح أجنبية مركبة (company_id, foreign_id) تمنع العلاقات بين الشركات، بما فيها المستخدم المرتبط بالمستأجر وموظف الصيانة ورافع المستند.
- دفعة الإيجار يجب أن تطابق شركة العقد ومستأجره ووحدته. طلب الصيانة يجب أن يطابق المبنى الفعلي للوحدة.
- الحذف مرجعيًا RESTRICT لحماية السجلات المالية. حذف مستخدم سجل التدقيق يضبط user_id إلى NULL.
- المبالغ DECIMAL(12,2)، المساحات DECIMAL(10,2)، التواريخ عبر casts، القيم المسموحة عبر ENUM.
- فهارس للشركة والحالة والاستحقاق والنشر والتكليف والمراجع polymorphic.
- documents يستخدم morph map ثابتًا؛ حدث saving يتحقق من انتماء الأب للشركة. الملفات على القرص local الخاص وتُنزل عبر policy؛ لا تنشرها بواسطة storage:link.
- AuditLog يحتفظ بمرجعه حتى عند حذف الأصل. خدمة الصيانة تسجل الإنشاء والتعيين وتغيير الحالة والإكمال مع قيم old/new؛ Timeline والملاحظات في maintenance_activities.
- علامات upcoming_notified_at وoverdue_notified_at تمنع تكرار تذكير الدفعة. PDF يُولّد عند الطلب ولا ينشئ ملفًا عامًا.
- الإعلان all يعني جميع أفراد الشركة المالكة؛ لا يتجاوز حدود الشركة.
