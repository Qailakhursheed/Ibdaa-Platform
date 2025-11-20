(function() {
    // مسار الشعار - يمكن تعديله حسب الحاجة
    const LOGO_PATH = 'photos/Sh.jpg';

    function addWatermark() {
        // تحديد جميع الصور التي لم يتم إضافة علامة مائية لها
        const images = document.querySelectorAll('img:not([data-watermarked])');
        
        images.forEach(img => {
            // 1. استثناء الشعار نفسه والصور الصغيرة جداً
            if (img.src.includes('Sh.jpg') || (img.width > 0 && img.width < 50) || (img.height > 0 && img.height < 50)) {
                img.setAttribute('data-watermarked', 'true');
                return;
            }

            // 2. إنشاء حاوية (Wrapper)
            const wrapper = document.createElement('div');
            wrapper.classList.add('watermark-wrapper');
            
            // 3. تحليل الأنماط والفئات
            const computed = window.getComputedStyle(img);
            const isAbsolute = computed.position === 'absolute';
            
            wrapper.style.position = isAbsolute ? 'absolute' : 'relative';
            
            // نقل خصائص التموضع إذا كانت الصورة مطلقة
            if (isAbsolute) {
                wrapper.style.top = computed.top;
                wrapper.style.left = computed.left;
                wrapper.style.right = computed.right;
                wrapper.style.bottom = computed.bottom;
                wrapper.style.zIndex = computed.zIndex;
                img.style.position = 'static'; // إعادة تعيين موضع الصورة داخل الحاوية
            } else {
                // إذا كانت الصورة عادية، الحاوية تأخذ نفس نوع العرض
                wrapper.style.display = computed.display === 'block' ? 'block' : 'inline-block';
            }

            // نقل فئات التنسيق (Tailwind classes) التي تؤثر على التخطيط والحجم
            const layoutClassesToMove = [
                'absolute', 'relative', 'fixed', 'sticky',
                'inset-0', 'top-0', 'left-0', 'right-0', 'bottom-0',
                'flex-shrink-0', 'flex-grow', 'flex-1',
                'm-auto', 'mx-auto', 'my-auto',
                'rounded', 'rounded-lg', 'rounded-full', 'rounded-xl', 'rounded-2xl',
                'shadow', 'shadow-lg', 'shadow-md', 'shadow-xl',
                'scroll-item', 'card-hover', 'border', 'border-2', 'border-4'
            ];
            
            const allClasses = [...img.classList];
            allClasses.forEach(cls => {
                // نقل الفئات الهيكلية والجمالية
                if (layoutClassesToMove.includes(cls) || 
                    /^(m-|p-|z-|inset-|top-|bottom-|left-|right-|border-|rounded-|shadow-)/.test(cls)) {
                    wrapper.classList.add(cls);
                    img.classList.remove(cls);
                }
                // نسخ فئات الحجم (تكرارها للحاوية والصورة)
                else if (/^(w-|h-|max-w-|max-h-|min-w-|min-h-)/.test(cls)) {
                    wrapper.classList.add(cls);
                    // لا نحذفها من الصورة لضمان ملئها للحاوية بشكل صحيح
                }
            });

            // نسخ الأبعاد المحددة بـ inline styles
            if (img.style.width) wrapper.style.width = img.style.width;
            if (img.style.height) wrapper.style.height = img.style.height;

            // التأكد من قص المحتوى الزائد (للحواف الدائرية)
            wrapper.style.overflow = 'hidden';

            // 4. إدراج الحاوية في DOM
            if (img.parentNode) {
                img.parentNode.insertBefore(wrapper, img);
                wrapper.appendChild(img);
            }

            // 5. ضبط تنسيق الصورة
            // إذا لم يكن للصورة فئات حجم، نجعلها تملأ الحاوية (التي ستأخذ حجم المحتوى)
            // ولكن إذا كررنا فئات الحجم، فالصورة لديها حجم بالفعل.
            // لضمان التوافق مع object-cover:
            if (!img.classList.contains('w-full') && !img.style.width) {
                img.style.width = '100%';
            }
            if (!img.classList.contains('h-full') && !img.style.height) {
                img.style.height = '100%';
            }
            
            img.style.objectFit = computed.objectFit || 'cover';
            
            // 6. إضافة العلامة المائية
            const watermark = document.createElement('img');
            watermark.src = LOGO_PATH;
            watermark.alt = 'Watermark';
            watermark.style.position = 'absolute';
            watermark.style.bottom = '10px';
            watermark.style.right = '10px'; // يمين أسفل
            watermark.style.width = '15%'; // حجم نسبي
            watermark.style.minWidth = '30px';
            watermark.style.maxWidth = '80px';
            watermark.style.opacity = '0.6'; // شفافية
            watermark.style.pointerEvents = 'none'; // عدم اعتراض النقرات
            watermark.style.zIndex = '50';
            
            wrapper.appendChild(watermark);
            
            // تعليم الصورة كمعالجة
            img.setAttribute('data-watermarked', 'true');
        });
    }

    // تشغيل الدالة عند تحميل الصفحة
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addWatermark);
    } else {
        addWatermark();
    }
    
    // تشغيل دوري للتعامل مع المحتوى الديناميكي (مثل السلايدر)
    setInterval(addWatermark, 2000);
})();
