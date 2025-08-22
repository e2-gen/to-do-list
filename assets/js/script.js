// التفاعلات المحسنة لتطبيق To-Do List
document.addEventListener('DOMContentLoaded', function() {
    // تفعيل التواريخ في المستقبل فقط
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    
    dateInputs.forEach(input => {
        input.setAttribute('min', today);
    });
    
    // رسائل التأكيد عند الحذف
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('هل أنت متأكد من أنك تريد حذف هذه المهمة؟')) {
                e.preventDefault();
            }
        });
    });
    
    // تأثيرات عند التمرير
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // مراقبة العناصر لإضافة تأثيرات التمرير
    document.querySelectorAll('.card, .task, .category').forEach(el => {
        observer.observe(el);
    });
    
    // فلترة المهام
    const filterButtons = document.querySelectorAll('.filter-btn');
    const tasks = document.querySelectorAll('.task');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // إزالة النشاط من جميع الأزرار
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // إضافة النشاط للزر المحدد
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            
            tasks.forEach(task => {
                switch(filter) {
                    case 'all':
                        task.style.display = 'flex';
                        break;
                    case 'completed':
                        if (task.classList.contains('completed')) {
                            task.style.display = 'flex';
                        } else {
                            task.style.display = 'none';
                        }
                        break;
                    case 'pending':
                        if (!task.classList.contains('completed')) {
                            task.style.display = 'flex';
                        } else {
                            task.style.display = 'none';
                        }
                        break;
                    case 'high':
                        if (task.classList.contains('priority-high')) {
                            task.style.display = 'flex';
                        } else {
                            task.style.display = 'none';
                        }
                        break;
                }
            });
        });
    });
    
    // البحث في المهام
    const searchInput = document.getElementById('task-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            tasks.forEach(task => {
                const title = task.querySelector('.task-details h3').textContent.toLowerCase();
                const description = task.querySelector('.task-description') ? 
                    task.querySelector('.task-description').textContent.toLowerCase() : '';
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    task.style.display = 'flex';
                } else {
                    task.style.display = 'none';
                }
            });
        });
    }
    
    // تحميل المزيد من المهام (تقسيم الصفحات)
    const loadMoreBtn = document.getElementById('load-more');
    if (loadMoreBtn) {
        let visibleTasks = 5;
        const allTasks = Array.from(tasks);
        
        // إخفاء المهام الزائدة عن العدد المحدد
        allTasks.slice(visibleTasks).forEach(task => {
            task.style.display = 'none';
        });
        
        loadMoreBtn.addEventListener('click', function() {
            visibleTasks += 5;
            
            allTasks.forEach((task, index) => {
                if (index < visibleTasks) {
                    task.style.display = 'flex';
                    setTimeout(() => {
                        task.classList.add('animate__animated', 'animate__fadeIn');
                    }, index * 100);
                }
            });
            
            // إخفاء زر "تحميل المزيد" إذا لم تبق مهام
            if (visibleTasks >= allTasks.length) {
                loadMoreBtn.style.display = 'none';
            }
        });
    }
    
    // عرض/إخفاء التفاصيل الإضافية
    tasks.forEach(task => {
        task.addEventListener('click', function(e) {
            if (e.target.tagName !== 'A' && !e.target.closest('a')) {
                const details = this.querySelector('.task-details');
                const description = this.querySelector('.task-description');
                
                if (description) {
                    description.classList.toggle('expanded');
                    
                    if (description.classList.contains('expanded')) {
                        description.style.maxHeight = description.scrollHeight + 'px';
                    } else {
                        description.style.maxHeight = '3em';
                    }
                }
            }
        });
    });
    
    // تحسين تجربة لوحة المفاتيح
    document.addEventListener('keydown', function(e) {
        // إضافة مهمة جديدة عند الضغط على Enter في حقل الإدخال
        if (e.key === 'Enter' && e.target.matches('input[type="text"]')) {
            e.target.closest('form').querySelector('button[type="submit"]').click();
        }
        
        // البحث عند الكتابة في حقل البحث
        if (e.target.matches('#task-search') && e.key.length === 1) {
            // البحث يتم تلقائياً عبر event listener مسبق
        }
    });
    
    // إشعارات التحديث
    const showNotification = (message, type = 'info') => {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button class="close-notification"><i class="fas fa-times"></i></button>
        `;
        
        document.body.appendChild(notification);
        
        // إظهار الإشعار
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // إخفاء الإشعار تلقائياً بعد 5 ثوان
        setTimeout(() => {
            closeNotification(notification);
        }, 5000);
        
        // إغلاق الإشعار عند النقر على الزر
        notification.querySelector('.close-notification').addEventListener('click', function() {
            closeNotification(notification);
        });
    };
    
    const closeNotification = (notification) => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    };
    
    // إضافة تأثيرات للعناصر عند التحميل
    setTimeout(() => {
        document.querySelectorAll('.card, .task, .category').forEach((el, index) => {
            el.style.animationDelay = `${index * 0.1}s`;
        });
    }, 100);
});

// تأثيرات CSS للرسومات المتحركة
const style = document.createElement('style');
style.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        color: #333;
        padding: 16px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 1000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 350px;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification.success {
        border-left: 4px solid #4ade80;
    }
    
    .notification.error {
        border-left: 4px solid #ef4444;
    }
    
    .notification.info {
        border-left: 4px solid #4361ee;
    }
    
    .close-notification {
        background: none;
        border: none;
        cursor: pointer;
        margin-left: auto;
        color: #64748b;
    }
    
    .task-description {
        max-height: 3em;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        transition: max-height 0.3s ease;
    }
    
    .animate__animated {
        animation-duration: 0.5s;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translate3d(0, 40px, 0);
        }
        to {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }
    }
    
    .animate__fadeInUp {
        animation-name: fadeInUp;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    
    .animate__fadeIn {
        animation-name: fadeIn;
    }
`;
document.head.appendChild(style);