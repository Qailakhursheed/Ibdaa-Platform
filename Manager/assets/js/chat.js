/**
 * نظام الرسائل والإشعارات - منصة إبداع
 * Internal Chat & Notifications System
 * Date: November 9, 2025
 */

// ============================================
// متغيرات عامة
// ============================================

let currentContactId = null;
let messagesPollingInterval = null;
let notificationsPollingInterval = null;
let lastMessageCount = 0;

// ============================================
// تهيئة نظام الرسائل
// ============================================

function initializeMessagingSystem() {
	// تحميل الإشعارات
	checkNewMessages();
	
	// بدء polling للإشعارات كل 5 ثوان
	notificationsPollingInterval = setInterval(checkNewMessages, 5000);
	
	// تحديد مستمعي الأحداث
	setupMessageEventListeners();
	
	console.log('✅ تم تهيئة نظام الرسائل بنجاح');
}

// ============================================
// التحقق من الرسائل الجديدة
// ============================================

async function checkNewMessages() {
	try {
		const response = await fetch('api/check_new_messages.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' }
		});
		
		const data = await response.json();
		
		if (data.success) {
			const newCount = data.new_count || 0;
			updateNotificationBadge(newCount);
			
			// تشغيل صوت إذا كانت هناك رسائل جديدة
			if (newCount > lastMessageCount && lastMessageCount > 0) {
				playNotificationSound();
			}
			
			lastMessageCount = newCount;
		}
	} catch (error) {
		console.error('خطأ في التحقق من الرسائل:', error);
	}
}

// ============================================
// تحديث شارة الإشعارات
// ============================================

function updateNotificationBadge(count) {
	const badges = document.querySelectorAll('.badge-counter');
	
	badges.forEach(badge => {
		if (count > 0) {
			badge.textContent = count > 99 ? '99+' : count;
			badge.classList.remove('hidden');
			badge.classList.add('notify-blink');
			
			// إضافة أنيميشن للجرس
			const bell = badge.closest('.notification-badge')?.querySelector('.notification-bell');
			if (bell) {
				bell.classList.add('has-new');
			}
		} else {
			badge.classList.add('hidden');
			badge.classList.remove('notify-blink');
			
			const bell = badge.closest('.notification-badge')?.querySelector('.notification-bell');
			if (bell) {
				bell.classList.remove('has-new');
			}
		}
	});
}

// ============================================
// تشغيل صوت الإشعار
// ============================================

function playNotificationSound() {
	try {
		const audio = new Audio('assets/sounds/notification.mp3');
		audio.volume = 0.5;
		audio.play().catch(err => {
			console.log('لم يتم تشغيل الصوت:', err);
		});
	} catch (error) {
		// Fail silently
	}
}

// ============================================
// عرض واجهة الرسائل
// ============================================

async function renderMessages() {
	setPageHeader('الرسائل', 'نظام المراسلة الداخلي');
	clearPageBody();
	
	const body = document.getElementById('pageBody');
	if (!body) return;
	
	body.innerHTML = `
		<section class="bg-white rounded-2xl shadow-xl">
			<div class="chat-container" dir="rtl">
				<!-- قائمة جهات الاتصال -->
				<div class="contacts-panel">
					<div class="contacts-header">
						<h3>المحادثات</h3>
					</div>
					<div class="contact-search">
						<input type="text" id="contactSearchInput" placeholder="بحث في جهات الاتصال...">
					</div>
					<div class="contacts-list" id="contactsList">
						<div class="loading-messages">
							<div class="loading-dot"></div>
							<div class="loading-dot"></div>
							<div class="loading-dot"></div>
						</div>
					</div>
				</div>
				
				<!-- صندوق الدردشة -->
				<div class="chat-box">
					<div id="chatContent" class="chat-empty-state">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
						</svg>
						<h3>مرحباً بك في نظام المراسلة</h3>
						<p>اختر جهة اتصال لبدء المحادثة</p>
					</div>
				</div>
			</div>
		</section>
	`;
	
	// تحميل جهات الاتصال
	await loadContacts();
	
	// تهيئة مستمعي الأحداث
	setupChatEventListeners();
	
	lucide.createIcons();
}

// ============================================
// تحميل قائمة جهات الاتصال
// ============================================

async function loadContacts(searchQuery = '') {
	try {
		const url = searchQuery 
			? `api/manage_messages.php?mode=recipients&q=${encodeURIComponent(searchQuery)}`
			: 'api/manage_messages.php?mode=recipients';
		
		const response = await fetch(url);
		const data = await response.json();
		
		if (!data.success) {
			throw new Error(data.message || 'فشل تحميل جهات الاتصال');
		}
		
		const contacts = data.recipients || [];
		const contactsList = document.getElementById('contactsList');
		
		if (contacts.length === 0) {
			contactsList.innerHTML = `
				<div style="padding: 20px; text-align: center; color: #9ca3af;">
					<p>لا توجد جهات اتصال</p>
				</div>
			`;
			return;
		}
		
		contactsList.innerHTML = contacts.map(contact => buildContactCard(contact)).join('');
		
		// إضافة مستمعي النقر
		contactsList.querySelectorAll('.contact-item').forEach(item => {
			item.addEventListener('click', () => {
				const contactId = parseInt(item.dataset.contactId, 10);
				selectContact(contactId);
			});
		});
		
	} catch (error) {
		const contactsList = document.getElementById('contactsList');
		contactsList.innerHTML = `
			<div style="padding: 20px; text-align: center; color: #ef4444;">
				<p>خطأ: ${error.message}</p>
			</div>
		`;
	}
}

// ============================================
// بناء بطاقة جهة الاتصال
// ============================================

function buildContactCard(contact) {
	const initials = contact.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
	const roleLabels = {
		'manager': 'المدير',
		'technical': 'المشرف الفني',
		'trainer': 'المدرب',
		'student': 'الطالب'
	};
	const roleLabel = roleLabels[contact.role] || contact.role;
	
	return `
		<div class="contact-item" data-contact-id="${contact.id}" dir="rtl">
			<div class="contact-avatar">${initials}</div>
			<div class="contact-info">
				<div class="contact-name">${escapeHtml(contact.full_name)}</div>
				<div class="contact-role">${roleLabel}</div>
			</div>
			${contact.unread_count > 0 ? `<span class="contact-unread">${contact.unread_count}</span>` : ''}
		</div>
	`;
}

// ============================================
// اختيار جهة اتصال
// ============================================

async function selectContact(contactId) {
	currentContactId = contactId;
	
	// تحديث UI
	document.querySelectorAll('.contact-item').forEach(item => {
		item.classList.toggle('active', parseInt(item.dataset.contactId, 10) === contactId);
	});
	
	// تحميل المحادثة
	await loadConversation(contactId);
	
	// وضع علامة "مقروءة" على الرسائل
	markMessagesAsRead(contactId);
	
	// بدء polling لتحديث المحادثة كل 3 ثوان
	if (messagesPollingInterval) {
		clearInterval(messagesPollingInterval);
	}
	messagesPollingInterval = setInterval(() => loadConversation(contactId, true), 3000);
}

// ============================================
// تحميل المحادثة
// ============================================

async function loadConversation(contactId, silent = false) {
	try {
		const response = await fetch(`api/manage_messages.php?with=${contactId}&limit=100`);
		const data = await response.json();
		
		if (!data.success) {
			throw new Error(data.message || 'فشل تحميل المحادثة');
		}
		
		const messages = data.messages || [];
		
		// الحصول على معلومات جهة الاتصال
		const contactItem = document.querySelector(`.contact-item[data-contact-id="${contactId}"]`);
		const contactName = contactItem?.querySelector('.contact-name')?.textContent || 'مستخدم';
		const contactRole = contactItem?.querySelector('.contact-role')?.textContent || '';
		
		const chatContent = document.getElementById('chatContent');
		
		chatContent.innerHTML = `
			<div class="chat-header">
				<div class="chat-header-info">
					<h4>${escapeHtml(contactName)}</h4>
					<p>${escapeHtml(contactRole)}</p>
				</div>
				<div class="chat-header-actions">
					<button onclick="loadConversation(${contactId})" title="تحديث">
						<i data-lucide="refresh-cw" style="width: 16px; height: 16px;"></i>
					</button>
				</div>
			</div>
			
			<div class="messages-area" id="messagesArea">
				${messages.length === 0 ? `
					<div class="chat-empty-state">
						<p>لا توجد رسائل بعد. ابدأ المحادثة!</p>
					</div>
				` : messages.map(msg => buildMessageBubble(msg)).join('')}
			</div>
			
			<div class="chat-input-area">
				<textarea 
					id="messageInput" 
					class="chat-input" 
					placeholder="اكتب رسالتك هنا..."
					rows="1"
				></textarea>
				<button 
					class="chat-send-btn" 
					id="sendMessageBtn"
					title="إرسال">
					<i data-lucide="send" style="width: 20px; height: 20px;"></i>
				</button>
			</div>
		`;
		
		lucide.createIcons();
		
		// التمرير للأسفل
		const messagesArea = document.getElementById('messagesArea');
		if (messagesArea) {
			messagesArea.scrollTop = messagesArea.scrollHeight;
		}
		
		// إعداد مستمعي الإرسال
		setupSendMessageListeners();
		
	} catch (error) {
		if (!silent) {
			showToast('خطأ: ' + error.message, 'error');
		}
	}
}

// ============================================
// بناء فقاعة الرسالة
// ============================================

function buildMessageBubble(message) {
	const isSent = message.sender_id === CURRENT_USER.id;
	const wrapperClass = isSent ? 'sent' : 'received';
	const time = formatTime(message.created_at);
	const isRead = message.is_read == 1;
	
	return `
		<div class="message-wrapper ${wrapperClass}" dir="rtl">
			<div class="message-bubble">
				${message.subject && message.subject !== 'بدون عنوان' ? `
					<div style="font-weight: 600; margin-bottom: 4px; font-size: 13px;">
						${escapeHtml(message.subject)}
					</div>
				` : ''}
				<div class="message-text">${escapeHtml(message.body)}</div>
				<span class="message-time">${time}</span>
				${isSent ? `
					<span class="message-status ${isRead ? 'seen' : 'sent'}">
						${isRead ? '✓✓ مقروءة' : '✓ مُرسلة'}
					</span>
				` : ''}
			</div>
		</div>
	`;
}

// ============================================
// إعداد مستمعي إرسال الرسالة
// ============================================

function setupSendMessageListeners() {
	const input = document.getElementById('messageInput');
	const sendBtn = document.getElementById('sendMessageBtn');
	
	if (!input || !sendBtn) return;
	
	// إرسال عند الضغط على الزر
	sendBtn.addEventListener('click', () => sendMessage());
	
	// إرسال عند Ctrl+Enter
	input.addEventListener('keydown', (e) => {
		if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
			e.preventDefault();
			sendMessage();
		}
	});
	
	// تمكين/تعطيل زر الإرسال
	input.addEventListener('input', () => {
		sendBtn.disabled = input.value.trim() === '';
	});
}

// ============================================
// إرسال رسالة
// ============================================

async function sendMessage() {
	const input = document.getElementById('messageInput');
	const sendBtn = document.getElementById('sendMessageBtn');
	
	if (!input || !currentContactId) return;
	
	const messageText = input.value.trim();
	if (messageText === '') return;
	
	sendBtn.disabled = true;
	
	try {
		const response = await fetch('api/manage_messages.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({
				action: 'send',
				recipient_id: currentContactId,
				subject: '',
				body: messageText
			})
		});
		
		const data = await response.json();
		
		if (!data.success) {
			throw new Error(data.message || 'فشل إرسال الرسالة');
		}
		
		// مسح النص
		input.value = '';
		
		// تحديث المحادثة
		await loadConversation(currentContactId);
		
		showToast('تم إرسال الرسالة بنجاح', 'success');
		
	} catch (error) {
		showToast('خطأ: ' + error.message, 'error');
	} finally {
		sendBtn.disabled = false;
		input.focus();
	}
}

// ============================================
// وضع علامة مقروءة على الرسائل
// ============================================

async function markMessagesAsRead(contactId) {
	try {
		await fetch('api/manage_messages.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({
				action: 'mark_all_read'
			})
		});
		
		// تحديث العداد
		checkNewMessages();
		
		// تحديث شارة جهة الاتصال
		const contactItem = document.querySelector(`.contact-item[data-contact-id="${contactId}"]`);
		const unreadBadge = contactItem?.querySelector('.contact-unread');
		if (unreadBadge) {
			unreadBadge.remove();
		}
		
	} catch (error) {
		console.error('خطأ في تحديث حالة الرسائل:', error);
	}
}

// ============================================
// إعداد مستمعي الأحداث
// ============================================

function setupChatEventListeners() {
	// بحث في جهات الاتصال
	const searchInput = document.getElementById('contactSearchInput');
	if (searchInput) {
		let searchTimeout;
		searchInput.addEventListener('input', (e) => {
			clearTimeout(searchTimeout);
			searchTimeout = setTimeout(() => {
				loadContacts(e.target.value);
			}, 300);
		});
	}
}

function setupMessageEventListeners() {
	// ربط زر الرسائل بفتح واجهة الدردشة
	const messagesBell = document.getElementById('messagesBell');
	if (messagesBell) {
		messagesBell.addEventListener('click', (e) => {
			e.preventDefault();
			// التوجيه إلى صفحة الرسائل إذا كانت موجودة
			if (typeof renderMessages === 'function') {
				renderMessages();
			} else {
				// أو استخدام نظام التنقل الموجود
				const messagesLink = document.querySelector('[data-page="messages"]');
				if (messagesLink) {
					messagesLink.click();
				} else {
					console.log('لا توجد واجهة دردشة متاحة');
					showToast('نظام الدردشة غير متاح حالياً', 'info');
				}
			}
		});
	}
	
	// التنظيف عند مغادرة الصفحة
	window.addEventListener('beforeunload', () => {
		if (messagesPollingInterval) {
			clearInterval(messagesPollingInterval);
		}
		if (notificationsPollingInterval) {
			clearInterval(notificationsPollingInterval);
		}
	});
}

// ============================================
// دوال مساعدة
// ============================================

function formatTime(datetime) {
	if (!datetime) return '';
	const date = new Date(datetime);
	const now = new Date();
	const diff = now - date;
	
	// أقل من دقيقة
	if (diff < 60000) {
		return 'الآن';
	}
	
	// أقل من ساعة
	if (diff < 3600000) {
		const minutes = Math.floor(diff / 60000);
		return `منذ ${minutes} دقيقة`;
	}
	
	// نفس اليوم
	if (date.toDateString() === now.toDateString()) {
		return date.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
	}
	
	// الأمس
	const yesterday = new Date(now);
	yesterday.setDate(yesterday.getDate() - 1);
	if (date.toDateString() === yesterday.toDateString()) {
		return 'أمس ' + date.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
	}
	
	// تاريخ كامل
	return date.toLocaleDateString('ar-EG', { 
		year: 'numeric', 
		month: 'short', 
		day: 'numeric',
		hour: '2-digit',
		minute: '2-digit'
	});
}
