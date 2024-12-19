<?php
session_start();
// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: 1loginpage.php');
    exit();
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../assets/css/1Journalcss.css">
 
  <title>Vision Board with Journal</title>

  <style>
    /* Form positioning at bottom of nav */
.sidebar form {
    margin-top: auto !important; /* Override inline style */
    margin-bottom: 2rem;
    width: 100%;
}

/* Purple Gradient Logout Button */
.logout-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.875rem 1.25rem;
    background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%);
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.logout-btn i {
    font-size: 1.25rem;
    transition: transform 0.3s ease;
}

.logout-btn span {
    font-weight: 500;
}

.logout-btn:hover {
    background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(139, 92, 246, 0.2);
}

.logout-btn:active {
    transform: translateY(0);
}

/* Hover effect for icon */
.logout-btn:hover i {
    transform: translateX(3px);
}

/* Focus state for accessibility */
.logout-btn:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.4);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sidebar form {
        margin-bottom: 1rem;
    }
    
    .logout-btn {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
    
    .logout-btn i {
        font-size: 1.1rem;
    }
}
  </style>
</head>
<body>
  <!-- Sidebar Navigation -->
  <aside id="sidebar">
  <nav class="sidebar">
        <div class="sidebar-logo">
            <h1>Habitude</h1>
        </div>
        <a href="1dashboard.php" class="nav-link">
            <i data-lucide="layout-dashboard"></i> Dashboard
        </a>
        <a href="1Journal.php" class="nav-link active">
            <i data-lucide="book-open"></i> Journal
        </a>
        <a href="1timer.php" class="nav-link">
            <i data-lucide="timer"></i> Timer
        </a>
        <a href="1visionboard.php" class="nav-link">
            <i data-lucide="target"></i> Vision Board
        </a>
        <form action="logout_user.php" method="POST" style="margin-top: 10px;">
    <button type="submit" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </form>
    </nav>
  </aside>

  <button id="sidebar-toggle">☰</button>

  <main id="main-content">
  <header>
      <h1>🎯 My Journal</h1>
      <div class="header-actions">
          <a href="add-journal.php" class="action-btn btn-view" style="text-decoration: none; margin-right: 10px;">Add Journal</a>
          <input type="text" id="searchInput" placeholder="Search entries..." class="search-input">
      </div>
  </div>
    </header>

    
    <section id="vision-board">
      <div class="board-header">
        <input type="text" id="board-title" placeholder="Enter board title" value="My Aspirations">
        <button id="save-title" class="button">Save Title</button>
      </div>
      <div id="board-container">
        <!-- Dynamically added images and text will appear here -->
      </div>
      
    <section id="journal-section">
      <h2 id="journal-title">My Journal</h2>
      <textarea id="journal-entry" placeholder="Write a new journal entry..."></textarea>
      <select id="mood-select">
          <option value="neutral">Neutral</option>
          <option value="happy">Happy</option>
          <option value="sad">Sad</option>
          <option value="excited">Excited</option>
          <option value="calm">Calm</option>
      </select>
      <input type="text" id="tags-input" placeholder="Enter tags (comma-separated)">
      <div class="button-section">
        <button id="add-entry-btn" class="button">Add Journal Entry</button>
        <button id="update-entry-btn" class="button" style="display:none;">Update Journal Entry</button>
      </div>
      <div id="success-message" style="display:none; color: green;"></div>

      <div id="journal-entries-container">
        <!-- Dynamically added journal entries will appear here -->
      </div>
    </section>

    <section id="manage-journals-section">
      <h2>Manage Journals:</h2>
      <div class="button-section">
        <button id="view-journals-btn" class="button">View Saved Journals</button>
      </div>
      <div id="saved-journals-container" style="display:none;">
        <!-- Saved journals will be displayed here -->
      </div>
    </section>
  </main>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
  console.log('DOM fully loaded, script is running...');

  // Global variables
  let currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
  let journalEntries = [];
  let editingEntryId = null;

  console.log('Current User ID:', currentUserId);

  // Utility function: Display messages
  function showMessage(message, type = 'success') {
    const messageContainer = document.getElementById(`${type}-message`);
    if (messageContainer) {
      messageContainer.textContent = message;
      messageContainer.style.display = 'block';
      setTimeout(() => {
        messageContainer.style.display = 'none';
      }, 3000);
    }
  }

  // Fetch Journal Entries
  async function fetchJournalEntries() {
    if (!currentUserId) {
      console.error('User not logged in');
      return;
    }

    try {
      const response = await fetch('../../functions/retrieveJournal.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ user_id: currentUserId }),
      });

      const data = await response.json();
      
      if (data.success) {
        journalEntries = data.entries || [];
        displayJournalEntries();
      } else {
        console.error('Failed to fetch journal entries:', data.message);
        showMessage(data.message || 'Failed to retrieve journal entries.', 'error');
      }
    } catch (error) {
      console.error('Error fetching journal entries:', error);
      showMessage('Failed to retrieve journal entries. Check console for details.', 'error');
    }
  }

  // Display Journal Entries
  function displayJournalEntries() {
    const journalEntriesContainer = document.getElementById('journal-entries-container');
    journalEntriesContainer.innerHTML = ''; // Clear the container

    if (journalEntries.length > 0) {
      journalEntries.forEach(entry => {
        const entryElement = document.createElement('div');
        entryElement.classList.add('journal-entry');

        entryElement.innerHTML = `
          <p>${entry.content}</p>
          <p>Mood: ${entry.mood}</p>
          <p>Tags: ${(Array.isArray(entry.tags) ? entry.tags : JSON.parse(entry.tags || '[]')).join(', ')}</p>
          <div class="entry-actions">
            <button class="button edit-btn" data-id="${entry.entry_id}">Edit</button>
            <button class="button delete-btn" data-id="${entry.entry_id}">Delete</button>
          </div>
        `;

        journalEntriesContainer.appendChild(entryElement);
      });

      // Attach event listeners for edit and delete buttons
      document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => editJournalEntry(button.dataset.id));
      });
      document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', () => deleteJournalEntry(button.dataset.id));
      });
    } else {
      journalEntriesContainer.innerHTML = '<p>No journal entries found.</p>';
    }
  }

  // Add Entry
  document.getElementById('add-entry-btn')?.addEventListener('click', async () => {
    const entryText = document.getElementById('journal-entry').value.trim();
    const mood = document.getElementById('mood-select')?.value || 'neutral';
    const tags = document.getElementById('tags-input')?.value.split(',').map(tag => tag.trim()) || [];

    if (!entryText) {
      alert('Please write a journal entry.');
      return;
    }

    try {
      const response = await fetch('../../functions/saveJournal.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: currentUserId,
          content: entryText,
          mood,
          tags,
        }),
      });

      const data = await response.json();
      if (data.success) {
        fetchJournalEntries();
        document.getElementById('journal-entry').value = '';
        document.getElementById('tags-input').value = '';
        showMessage('Journal entry successfully added!');
      } else {
        showMessage(`Failed to save entry: ${data.message}`, 'error');
      }
    } catch (error) {
      console.error('Error saving journal entry:', error);
      showMessage('Error saving entry. Check console for details.', 'error');
    }
  });

  // Edit Entry
  function editJournalEntry(entryId) {
    const entry = journalEntries.find(e => e.entry_id == entryId);
    if (!entry) {
      alert('Entry not found.');
      return;
    }

    document.getElementById('journal-entry').value = entry.content;
    document.getElementById('tags-input').value = (Array.isArray(entry.tags) ? entry.tags : JSON.parse(entry.tags || '[]')).join(', ');
    document.getElementById('mood-select').value = entry.mood || 'neutral';

    editingEntryId = entryId;
    document.getElementById('add-entry-btn').style.display = 'none';
    document.getElementById('update-entry-btn').style.display = 'block';
  }

  // Update Entry
  document.getElementById('update-entry-btn')?.addEventListener('click', async () => {
    const entryText = document.getElementById('journal-entry').value.trim();
    const mood = document.getElementById('mood-select')?.value || 'neutral';
    const tags = document.getElementById('tags-input')?.value.split(',').map(tag => tag.trim()) || [];

    if (!entryText || !editingEntryId) {
      alert('Please write a journal entry and select one to update.');
      return;
    }

    try {
      const response = await fetch('../../functions/updateJournal.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: currentUserId,
          entry_id: editingEntryId,
          content: entryText,
          mood,
          tags,
        }),
      });

      const data = await response.json();
      if (data.success) {
        fetchJournalEntries();
        document.getElementById('journal-entry').value = '';
        document.getElementById('tags-input').value = '';
        editingEntryId = null;
        document.getElementById('add-entry-btn').style.display = 'block';
        document.getElementById('update-entry-btn').style.display = 'none';
        showMessage('Entry successfully updated!');
      } else {
        showMessage(`Failed to update entry: ${data.message}`, 'error');
      }
    } catch (error) {
      console.error('Error updating journal entry:', error);
      showMessage('Error updating entry. Check console for details.', 'error');
    }
  });

  // Delete Entry
  async function deleteJournalEntry(entryId) {
    if (confirm('Are you sure you want to delete this journal entry?')) {
      try {
        const response = await fetch('../../functions/deleteJournal.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ user_id: currentUserId, entry_id: entryId }),
        });

        const data = await response.json();
        if (data.success) {
          fetchJournalEntries();
          showMessage('Entry deleted successfully!');
        } else {
          showMessage(`Failed to delete entry: ${data.message}`, 'error');
        }
      } catch (error) {
        console.error('Error deleting journal entry:', error);
        showMessage('Error deleting entry. Check console for details.', 'error');
      }
    }
  }

  // View Journals Button
  document.getElementById('view-journals-btn')?.addEventListener('click', () => {
    const savedJournalsContainer = document.getElementById('saved-journals-container');
    if (savedJournalsContainer) {
      savedJournalsContainer.style.display =
        savedJournalsContainer.style.display === 'none' || savedJournalsContainer.style.display === '' ? 'block' : 'none';
    }
  });

  // Fetch entries on page load
  fetchJournalEntries();
});

  </script>

</body>
</html>