<?php
// ============================================================
//  Academon · Notes.php
//  Protected page - users can create, read, update, delete notes
// ============================================================

require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/config.php';

$user_id = $current_user['id'];
$username = htmlspecialchars($current_user['username']);

// Handle AJAX requests for notes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    try {
        $db = get_db();
        
        switch ($action) {
            case 'create':
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                $subject = trim($_POST['subject'] ?? 'General');
                
                if (empty($title)) {
                    $response = ['success' => false, 'message' => 'Title is required'];
                } else {
                    $stmt = $db->prepare('INSERT INTO notes (user_id, title, content, subject, created_at) VALUES (?, ?, ?, ?, NOW())');
                    $stmt->execute([$user_id, $title, $content, $subject]);
                    $response = [
                        'success' => true,
                        'message' => 'Note created successfully!',
                        'note_id' => $db->lastInsertId()
                    ];
                }
                break;
                
            case 'get':
                $note_id = intval($_POST['note_id'] ?? 0);
                $stmt = $db->prepare('SELECT * FROM notes WHERE id = ? AND user_id = ?');
                $stmt->execute([$note_id, $user_id]);
                $note = $stmt->fetch();
                
                if ($note) {
                    $response = ['success' => true, 'note' => $note];
                } else {
                    $response = ['success' => false, 'message' => 'Note not found'];
                }
                break;
                
            case 'update':
                $note_id = intval($_POST['note_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                $subject = trim($_POST['subject'] ?? 'General');
                
                if (empty($title)) {
                    $response = ['success' => false, 'message' => 'Title is required'];
                } else {
                    $stmt = $db->prepare('UPDATE notes SET title = ?, content = ?, subject = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
                    $stmt->execute([$title, $content, $subject, $note_id, $user_id]);
                    $response = ['success' => true, 'message' => 'Note updated successfully!'];
                }
                break;
                
            case 'delete':
                $note_id = intval($_POST['note_id'] ?? 0);
                $stmt = $db->prepare('DELETE FROM notes WHERE id = ? AND user_id = ?');
                $stmt->execute([$note_id, $user_id]);
                
                if ($stmt->rowCount() > 0) {
                    $response = ['success' => true, 'message' => 'Note deleted successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Note not found'];
                }
                break;
                
            case 'list':
                $stmt = $db->prepare('SELECT * FROM notes WHERE user_id = ? ORDER BY updated_at DESC, created_at DESC');
                $stmt->execute([$user_id]);
                $notes = $stmt->fetchAll();
                $response = ['success' => true, 'notes' => $notes];
                break;
        }
    } catch (PDOException $e) {
        error_log('Notes error: ' . $e->getMessage());
        $response = ['success' => false, 'message' => 'Database error occurred'];
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Academon · My Notes</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            min-height: 100vh;
            background: url('sunset.png') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Press Start 2P', monospace;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255,241,203,0.92);
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 30px;
            border: 3px solid rgba(255,215,100,0.8);
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            font-size: 14px;
            color: #1e1a2b;
        }
        
        .header h1 .emoji {
            font-size: 24px;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .user-name {
            font-size: 9px;
            color: #5a4e70;
        }
        
        .back-btn {
            background: #2c2025;
            color: white;
            font-family: 'Press Start 2P', monospace;
            font-size: 9px;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 40px;
            box-shadow: 0 4px 0 #0f0b0e;
            transition: 0.15s;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 0 #0f0b0e;
        }
        
        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: rgba(255,241,203,0.92);
            border: 2px solid rgba(255,215,100,0.6);
            border-radius: 15px;
            padding: 15px 20px;
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 24px;
            color: #8f8df4;
        }
        
        .stat-card .label {
            font-size: 7px;
            color: #5a4e70;
            margin-top: 5px;
        }
        
        /* Create Note Button */
        .create-btn {
            background: #8f8df4;
            color: white;
            font-family: 'Press Start 2P', monospace;
            font-size: 10px;
            padding: 14px 30px;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            box-shadow: 0 4px 0 #6b69b8;
            transition: 0.15s;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .create-btn:hover {
            background: #7a78d6;
            transform: translateY(-2px);
            box-shadow: 0 6px 0 #6b69b8;
        }
        
        .create-btn:active {
            transform: translateY(4px);
            box-shadow: 0 2px 0 #6b69b8;
        }
        
        /* Notes Grid */
        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .note-card {
            background: rgba(255,241,203,0.92);
            border: 2.5px solid rgba(255,215,100,0.75);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.24);
            transition: transform 0.15s;
            position: relative;
        }
        
        .note-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.28);
        }
        
        .note-subject {
            display: inline-block;
            font-size: 7px;
            padding: 3px 10px;
            border-radius: 20px;
            background: #8f8df4;
            color: white;
            margin-bottom: 10px;
        }
        
        .note-title {
            font-size: 11px;
            color: #1e1a2b;
            margin-bottom: 8px;
            word-break: break-word;
        }
        
        .note-content {
            font-size: 8px;
            color: #4a4060;
            line-height: 1.8;
            margin-bottom: 12px;
            word-break: break-word;
            max-height: 80px;
            overflow: hidden;
        }
        
        .note-date {
            font-size: 7px;
            color: #7a7090;
            margin-bottom: 12px;
        }
        
        .note-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .note-btn {
            font-family: 'Press Start 2P', monospace;
            font-size: 7px;
            padding: 6px 14px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: 0.15s;
            color: white;
            flex: 1;
            min-width: 60px;
        }
        
        .note-btn-edit {
            background: #ff9800;
            box-shadow: 0 3px 0 #f57c00;
        }
        
        .note-btn-edit:hover {
            background: #fb8c00;
            transform: translateY(-1px);
            box-shadow: 0 4px 0 #f57c00;
        }
        
        .note-btn-delete {
            background: #f44336;
            box-shadow: 0 3px 0 #d32f2f;
        }
        
        .note-btn-delete:hover {
            background: #e53935;
            transform: translateY(-1px);
            box-shadow: 0 4px 0 #d32f2f;
        }
        
        .note-btn-view {
            background: #4caf50;
            box-shadow: 0 3px 0 #388e3c;
        }
        
        .note-btn-view:hover {
            background: #43a047;
            transform: translateY(-1px);
            box-shadow: 0 4px 0 #388e3c;
        }
        
        .no-notes {
            text-align: center;
            color: #7a7090;
            font-size: 9px;
            padding: 60px 20px;
            grid-column: 1 / -1;
        }
        
        .no-notes .big-emoji {
            font-size: 48px;
            display: block;
            margin-bottom: 15px;
        }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: rgba(255,241,203,0.98);
            border: 3px solid rgba(255,215,100,0.85);
            border-radius: 25px;
            padding: 30px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        
        .modal h2 {
            font-size: 12px;
            color: #1e1a2b;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .modal label {
            font-size: 8px;
            color: #4a4060;
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        
        .modal input, .modal select, .modal textarea {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid rgba(30,26,43,0.2);
            border-radius: 12px;
            font-family: 'Press Start 2P', monospace;
            font-size: 8px;
            background: rgba(255,255,255,0.7);
            outline: none;
        }
        
        .modal input:focus, .modal select:focus, .modal textarea:focus {
            border-color: #8f8df4;
        }
        
        .modal textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .modal select {
            appearance: auto;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .modal-btn {
            font-family: 'Press Start 2P', monospace;
            font-size: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: 0.15s;
            color: white;
            flex: 1;
        }
        
        .modal-btn-save {
            background: #4caf50;
            box-shadow: 0 4px 0 #388e3c;
        }
        
        .modal-btn-save:hover {
            background: #43a047;
            transform: translateY(-2px);
            box-shadow: 0 6px 0 #388e3c;
        }
        
        .modal-btn-cancel {
            background: #9e9e9e;
            box-shadow: 0 4px 0 #757575;
        }
        
        .modal-btn-cancel:hover {
            background: #8e8e8e;
            transform: translateY(-2px);
            box-shadow: 0 6px 0 #757575;
        }
        
        .modal-btn-delete-modal {
            background: #f44336;
            box-shadow: 0 4px 0 #d32f2f;
        }
        
        .modal-btn-delete-modal:hover {
            background: #e53935;
            transform: translateY(-2px);
            box-shadow: 0 6px 0 #d32f2f;
        }
        
        .note-id-hidden {
            display: none;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header { flex-direction: column; text-align: center; }
            .header-actions { justify-content: center; }
            .notes-grid { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 480px) {
            .modal { padding: 20px; }
            .modal input, .modal select, .modal textarea { font-size: 7px; }
        }
        
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(0,0,0,0.05); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #8f8df4; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #7a78d6; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <span class="emoji">📝</span> MY NOTES
                <span style="font-size: 8px; color: #7a7090;">Study Notes</span>
            </h1>
            <div class="header-actions">
                <span class="user-name">👤 <?php echo $username; ?></span>
                <a href="dashboard.php" class="back-btn">⬅ BACK</a>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="number" id="totalNotes">0</div>
                <div class="label">📚 TOTAL NOTES</div>
            </div>
            <div class="stat-card">
                <div class="number" id="subjectsCount">0</div>
                <div class="label">📖 SUBJECTS</div>
            </div>
            <div class="stat-card">
                <div class="number" id="recentNote">-</div>
                <div class="label">🕐 LAST UPDATED</div>
            </div>
        </div>
        
        <!-- Create Note Button -->
        <button class="create-btn" onclick="openModal('create')">➕ CREATE NEW NOTE</button>
        
        <!-- Notes Grid -->
        <div class="notes-grid" id="notesGrid">
            <div class="no-notes" id="noNotes">
                <span class="big-emoji">📝</span>
                <p>No notes yet!</p>
                <p style="margin-top: 10px; font-size: 7px; color: #7a7090;">
                    Click "Create New Note" to start studying
                </p>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <h2 id="modalTitle">📝 Create New Note</h2>
            <input type="hidden" id="noteId" value="">
            
            <label for="noteSubject">📋 Subject</label>
            <select id="noteSubject">
                <option value="General">General</option>
                <option value="Mathematics">Mathematics</option>
                <option value="Science">Science</option>
                <option value="History">History</option>
                <option value="English">English</option>
                <option value="Filipino">Filipino</option>
                <option value="Programming">Programming</option>
                <option value="Other">Other</option>
            </select>
            
            <label for="noteTitle">📌 Title</label>
            <input type="text" id="noteTitle" placeholder="Enter note title..." />
            
            <label for="noteContent">📄 Content</label>
            <textarea id="noteContent" placeholder="Write your notes here..."></textarea>
            
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">CANCEL</button>
                <button class="modal-btn modal-btn-delete-modal" id="deleteBtn" onclick="deleteNote()" style="display:none;">DELETE</button>
                <button class="modal-btn modal-btn-save" id="saveBtn" onclick="saveNote()">💾 SAVE</button>
            </div>
        </div>
    </div>
    
    <script>
        // ============================================================
        // Notes JavaScript
        // ============================================================
        
        let currentMode = 'create';
        
        // Load notes on page load
        document.addEventListener('DOMContentLoaded', loadNotes);
        
        function loadNotes() {
            fetch('notes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=list'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayNotes(data.notes);
                    updateStats(data.notes);
                } else {
                    console.error('Error loading notes:', data.message);
                }
            })
            .catch(error => {
                console.error('Network error:', error);
            });
        }
        
        function displayNotes(notes) {
            const grid = document.getElementById('notesGrid');
            const noNotes = document.getElementById('noNotes');
            
            if (notes.length === 0) {
                grid.innerHTML = `
                    <div class="no-notes">
                        <span class="big-emoji">📝</span>
                        <p>No notes yet!</p>
                        <p style="margin-top: 10px; font-size: 7px; color: #7a7090;">
                            Click "Create New Note" to start studying
                        </p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            notes.forEach(note => {
                const subjectColors = {
                    'General': '#8f8df4',
                    'Mathematics': '#ff9800',
                    'Science': '#4caf50',
                    'History': '#f44336',
                    'English': '#2196f3',
                    'Filipino': '#9c27b0',
                    'Programming': '#00bcd4',
                    'Other': '#9e9e9e'
                };
                const color = subjectColors[note.subject] || '#8f8df4';
                
                const content = note.content.length > 100 ? note.content.substring(0, 100) + '...' : note.content;
                const date = new Date(note.updated_at || note.created_at);
                const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                
                html += `
                    <div class="note-card">
                        <span class="note-subject" style="background: ${color};">${note.subject}</span>
                        <h3 class="note-title">${escapeHtml(note.title)}</h3>
                        <div class="note-content">${escapeHtml(content)}</div>
                        <div class="note-date">🕐 ${formattedDate}</div>
                        <div class="note-actions">
                            <button class="note-btn note-btn-view" onclick="viewNote(${note.id})">👁 VIEW</button>
                            <button class="note-btn note-btn-edit" onclick="editNote(${note.id})">✏️ EDIT</button>
                            <button class="note-btn note-btn-delete" onclick="confirmDelete(${note.id})">🗑 DELETE</button>
                        </div>
                    </div>
                `;
            });
            
            grid.innerHTML = html;
        }
        
        function updateStats(notes) {
            document.getElementById('totalNotes').textContent = notes.length;
            
            const subjects = new Set(notes.map(n => n.subject));
            document.getElementById('subjectsCount').textContent = subjects.size;
            
            if (notes.length > 0) {
                const lastNote = notes[0];
                const date = new Date(lastNote.updated_at || lastNote.created_at);
                document.getElementById('recentNote').textContent = date.toLocaleDateString();
            } else {
                document.getElementById('recentNote').textContent = '-';
            }
        }
        
        function openModal(mode, noteData = null) {
            currentMode = mode;
            const overlay = document.getElementById('modalOverlay');
            const title = document.getElementById('modalTitle');
            const deleteBtn = document.getElementById('deleteBtn');
            
            if (mode === 'create') {
                title.textContent = '📝 Create New Note';
                document.getElementById('noteId').value = '';
                document.getElementById('noteSubject').value = 'General';
                document.getElementById('noteTitle').value = '';
                document.getElementById('noteContent').value = '';
                deleteBtn.style.display = 'none';
                document.getElementById('saveBtn').textContent = '💾 CREATE';
            } else if (mode === 'edit') {
                title.textContent = '✏️ Edit Note';
                document.getElementById('noteId').value = noteData.id;
                document.getElementById('noteSubject').value = noteData.subject;
                document.getElementById('noteTitle').value = noteData.title;
                document.getElementById('noteContent').value = noteData.content;
                deleteBtn.style.display = 'block';
                document.getElementById('saveBtn').textContent = '💾 UPDATE';
            } else if (mode === 'view') {
                title.textContent = '👁 View Note';
                document.getElementById('noteId').value = noteData.id;
                document.getElementById('noteSubject').value = noteData.subject;
                document.getElementById('noteTitle').value = noteData.title;
                document.getElementById('noteContent').value = noteData.content;
                deleteBtn.style.display = 'none';
                document.getElementById('saveBtn').textContent = 'CLOSE';
                document.getElementById('saveBtn').onclick = closeModal;
            }
            
            overlay.classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('modalOverlay').classList.remove('active');
            document.getElementById('saveBtn').onclick = saveNote;
        }
        
        function saveNote() {
            const noteId = document.getElementById('noteId').value;
            const subject = document.getElementById('noteSubject').value;
            const title = document.getElementById('noteTitle').value.trim();
            const content = document.getElementById('noteContent').value.trim();
            
            if (!title) {
                alert('⚠️ Please enter a title for your note.');
                return;
            }
            
            const action = noteId ? 'update' : 'create';
            const formData = new FormData();
            formData.append('action', action);
            formData.append('subject', subject);
            formData.append('title', title);
            formData.append('content', content);
            if (noteId) formData.append('note_id', noteId);
            
            fetch('notes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    loadNotes();
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Network error. Please try again.');
                console.error('Error:', error);
            });
        }
        
        function viewNote(noteId) {
            fetch('notes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get&note_id=' + noteId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    openModal('view', data.note);
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Network error.');
                console.error('Error:', error);
            });
        }
        
        function editNote(noteId) {
            fetch('notes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get&note_id=' + noteId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    openModal('edit', data.note);
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Network error.');
                console.error('Error:', error);
            });
        }
        
        function confirmDelete(noteId) {
            if (confirm('🗑 Are you sure you want to delete this note? This cannot be undone!')) {
                deleteNoteById(noteId);
            }
        }
        
        function deleteNote() {
            const noteId = document.getElementById('noteId').value;
            if (noteId) {
                deleteNoteById(noteId);
            }
        }
        
        function deleteNoteById(noteId) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('note_id', noteId);
            
            fetch('notes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    loadNotes();
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Network error.');
                console.error('Error:', error);
            });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>