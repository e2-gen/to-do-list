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
            if (!confirm('هل أنت متأكد من أنك تريد نقل هذه المهمة إلى الأرشيف؟')) {
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
    
    // تحميل المزيد من المهام
    const loadMoreButton = document.getElementById('load-more');
    if (loadMoreButton) {
        loadMoreButton.addEventListener('click', function() {
            const hiddenTasks = document.querySelectorAll('.task[style="display: none"]');
            
            for (let i = 0; i < 5 && i < hiddenTasks.length; i++) {
                hiddenTasks[i].style.display = 'flex';
            }
            
            if (document.querySelectorAll('.task[style="display: none"]').length === 0) {
                this.style.display = 'none';
            }
        });
    }
    
    // إضافة تأثيرات عند تمرير الماوس
    const addHoverEffects = () => {
        const elements = document.querySelectorAll('.task, .category, .btn-primary, .btn-secondary');
        
        elements.forEach(el => {
            el.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
            });
            
            el.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
            });
        });
    };
    
    addHoverEffects();
    
    // إضافة ميزة السحب والإفلات لإعادة ترتيب المهام
    if (document.getElementById('tasks-container')) {
        new Sortable(document.getElementById('tasks-container'), {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                // هنا يمكن إضافة كود لحفظ الترتيب الجديد في قاعدة البيانات
                console.log('تم تغيير ترتيب المهمة', evt.oldIndex, 'إلى', evt.newIndex);
            }
        });
    }
    
    // إضافة مؤقت للرسائل المنبثقة
    const messages = document.querySelectorAll('.alert');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            message.style.transition = 'opacity 0.5s ease';
            setTimeout(() => message.remove(), 500);
        }, 3000);
    });
});
