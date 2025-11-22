# 🤝 دليل المساهمة في مشروع منصة إبداع

شكراً لاهتمامك بالمساهمة في مشروع منصة إبداع! 🎉

---

## 📋 قائمة المحتويات

1. [سياسة السلوك](#سياسة-السلوك)
2. [بدء المساهمة](#بدء-المساهمة)
3. [عملية سير العمل](#عملية-سير-العمل)
4. [معايير الكود](#معايير-الكود)
5. [الاختبارات](#الاختبارات)
6. [نصائح الإرسال](#نصائح-الإرسال)

---

## 🤗 سياسة السلوك

### التزاماتنا

- نحن ملتزمون بتوفير بيئة ترحيبية وآمنة للجميع
- نقدر احترام الاختلافات والآراء المتنوعة
- نركز على النقاشات البناءة والتعاون

### السلوك المتوقع

✅ **المقبول:**
- احترام بعضنا البعض
- الاستماع الفعال والنقاش البناء
- التركيز على الفكرة وليس الشخص

❌ **غير المقبول:**
- التنمر أو الإساءة
- التعليقات المهينة أو التمييزية
- الضغط أو التهديدات

---

## 🚀 بدء المساهمة

### الخطوة 1: Fork المستودع

```bash
# اذهب إلى https://github.com/Ibdaa/Ibdaa-Taiz
# اضغط على زر Fork
```

### الخطوة 2: استنساخ المستودع المنسوخ

```bash
git clone https://github.com/YOUR_USERNAME/Ibdaa-Taiz.git
cd Ibdaa-Taiz
```

### الخطوة 3: إضافة المستودع الأصلي كـ Upstream

```bash
git remote add upstream https://github.com/Ibdaa/Ibdaa-Taiz.git
git fetch upstream
```

### الخطوة 4: إنشاء فرع جديد

```bash
# تحديث main من upstream
git checkout main
git pull upstream main

# إنشاء فرع جديد
git checkout -b feature/your-feature-name
```

---

## 🔄 عملية سير العمل

### 1. تطوير الميزة

```bash
# تأكد من تثبيت المتطلبات
composer install
npm install

# قم بإجراء التغييرات اللازمة
# ثم قم بالاختبار
npm test
./vendor/bin/phpunit
```

### 2. Commit التغييرات

```bash
# صيغة الـ commit
git commit -m "type: description"

# الأنواع المقبولة:
# - feat: ميزة جديدة
# - fix: إصلاح خطأ
# - docs: تحديث التوثيق
# - style: تغييرات في التنسيق
# - refactor: إعادة كود
# - perf: تحسينات الأداء
# - test: إضافة اختبارات

# أمثلة:
git commit -m "feat: add chat system for students"
git commit -m "fix: resolve database connection issue"
git commit -m "docs: update README with API documentation"
```

### 3. Push التغييرات

```bash
git push origin feature/your-feature-name
```

### 4. فتح Pull Request

1. اذهب إلى المستودع الأصلي على GitHub
2. اضغط على "New Pull Request"
3. اختر الفرع الخاص بك
4. اكتب وصفاً واضحاً للتغييرات
5. أرسل الطلب

---

## 📐 معايير الكود

### PHP

```php
<?php
// ✅ المعايير الصحيحة

namespace App\Controllers;

use App\Models\User;

class UserController {
    /**
     * الحصول على بيانات المستخدم
     *
     * @param int $id معرّف المستخدم
     * @return array بيانات المستخدم
     */
    public function getUserById($id) {
        // التحقق من صحة المدخلات
        if (!is_numeric($id) || $id <= 0) {
            return ['error' => 'Invalid ID'];
        }

        // استخدام Prepared Statements
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    /**
     * إنشاء مستخدم جديد
     *
     * @param array $data بيانات المستخدم
     * @return bool النتيجة
     */
    public function create(array $data) {
        // التحقق من البيانات المطلوبة
        $required = ['name', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field $field is required");
            }
        }
        
        // تشفير كلمة المرور
        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        unset($data['password']);
        
        // ... استكمال العملية
        return true;
    }
}
```

### JavaScript/Vue.js

```javascript
// ✅ المعايير الصحيحة

/**
 * مكون الدردشة
 */
export default {
    name: 'ChatComponent',
    
    props: {
        userId: {
            type: Number,
            required: true
        }
    },
    
    data() {
        return {
            messages: [],
            newMessage: '',
            isLoading: false
        };
    },
    
    computed: {
        // حساب الخصائص المشتقة
        sortedMessages() {
            return this.messages.sort((a, b) => 
                new Date(a.timestamp) - new Date(b.timestamp)
            );
        }
    },
    
    methods: {
        /**
         * جلب الرسائل من الخادم
         */
        async fetchMessages() {
            try {
                this.isLoading = true;
                const response = await fetch(`/api/messages/${this.userId}`);
                
                if (!response.ok) {
                    throw new Error('Failed to fetch messages');
                }
                
                this.messages = await response.json();
            } catch (error) {
                console.error('Error:', error);
                this.showError('Failed to load messages');
            } finally {
                this.isLoading = false;
            }
        },
        
        /**
         * إرسال رسالة جديدة
         */
        async sendMessage() {
            if (!this.newMessage.trim()) return;
            
            try {
                const response = await fetch('/api/messages/send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        recipientId: this.userId,
                        content: this.newMessage
                    })
                });
                
                if (response.ok) {
                    this.newMessage = '';
                    await this.fetchMessages();
                }
            } catch (error) {
                this.showError('Failed to send message');
            }
        }
    },
    
    mounted() {
        this.fetchMessages();
        // تحديث الرسائل كل 3 ثوان
        setInterval(() => this.fetchMessages(), 3000);
    }
};
```

### معايير عامة

- **الأسماء**: استخدم أسماء واضحة ووصفية
- **التعليقات**: أضف تعليقات للكود المعقد
- **الفراغات**: استخدم indentation بـ 4 spaces
- **الطول**: حافظ على الدوال أقصر من 50 سطر
- **DRY**: لا تكرر الكود

---

## 🧪 الاختبارات

### كتابة الاختبارات

```php
<?php
// tests/UserTest.php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserTest extends TestCase {
    
    private $user;
    
    protected function setUp(): void {
        parent::setUp();
        $this->user = new User();
    }
    
    /**
     * اختبار إنشاء مستخدم جديد
     */
    public function testCreateUser() {
        $result = $this->user->create([
            'name' => 'أحمد',
            'email' => 'ahmed@example.com',
            'password' => 'secure_password'
        ]);
        
        $this->assertTrue($result);
    }
    
    /**
     * اختبار فشل إنشاء مستخدم ببيانات ناقصة
     */
    public function testCreateUserWithMissingData() {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->user->create(['name' => 'أحمد']);
    }
    
    /**
     * اختبار جلب المستخدم
     */
    public function testGetUserById() {
        // الإعداد
        $userId = 1;
        
        // التنفيذ
        $user = $this->user->getById($userId);
        
        // التحقق
        $this->assertIsArray($user);
        $this->assertEquals($userId, $user['id']);
    }
}
```

### تشغيل الاختبارات

```bash
# تشغيل جميع الاختبارات
./vendor/bin/phpunit

# تشغيل اختبار محدد
./vendor/bin/phpunit tests/UserTest.php

# مع التقارير
./vendor/bin/phpunit --coverage-html coverage/

# مع الإخراج المفصل
./vendor/bin/phpunit -v
```

---

## 📝 نصائح الإرسال

### قائمة التحقق قبل الإرسال

- [ ] هل قمت بـ fork المستودع؟
- [ ] هل قمت بإنشاء فرع جديد؟
- [ ] هل اتبعت معايير الكود؟
- [ ] هل أضفت الاختبارات؟
- [ ] هل نجحت جميع الاختبارات؟
- [ ] هل حدثت التوثيق؟
- [ ] هل الـ commits واضحة؟
- [ ] هل وصف PR واضح؟

### نموذج وصف Pull Request

```markdown
## 📝 الوصف
وصف موجز لما يفعله هذا PR

## 🎯 النوع
- [ ] ميزة جديدة
- [ ] إصلاح خطأ
- [ ] تحسين الأداء
- [ ] تحديث التوثيق

## 🧪 الاختبارات
وصف الاختبارات التي تم إجراؤها

## ✅ قائمة التحقق
- [ ] قمت بقراءة الـ CONTRIBUTING
- [ ] تم اختبار الكود محلياً
- [ ] تم إضافة اختبارات جديدة
- [ ] التوثيق محدثة
- [ ] لا توجد warnings

## 📸 الصور (اختياري)
أضف صوراً إذا لزم الأمر
```

---

## 🆘 الحصول على المساعدة

- 📧 البريد الإلكتروني: support@ibdaa.com
- 💬 Discussions على GitHub
- 🐦 تويتر: @IbdaaTraining

---

## 📚 موارد إضافية

- [دليل Git](https://git-scm.com/book/ar)
- [PHP Best Practices](https://www.phptherightway.com/)
- [Vue.js Documentation](https://vuejs.org/guide/)
- [الاختبار مع PHPUnit](https://phpunit.de/documentation.html)

---

شكراً مرة أخرى على مساهمتك! 🚀

**آخر تحديث: 21 نوفمبر 2025**
