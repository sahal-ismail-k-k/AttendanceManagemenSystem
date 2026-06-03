"""
Final comprehensive update for all remaining pages
"""
import os
import re

BASE_DIR = r"d:\NOYEL\attendance\attendance_controller_BACKUP"

# Files to update
FILES = [
    'view_attendancet.php',
    'add_notification.php',
    'todays_sessions.php',
    'edit_attendance.php',
    'change_passwords.php',
    'change_passwordt.php',
    'change_passworda.php',
    'my_batches.php',
    'report_attendance.php',
]

# Comprehensive color replacements
REPLACEMENTS = {
    # Old colors -> New light aesthetic
    '#2c3e50': '#a78bfa',
    '#34495e': '#c4b5fd',
    '#3498db': '#fbbf24',
    '#5dade2': '#fbbf24',
    '#4a90e2': '#a78bfa',
    '#4895ef': '#c4b5fd',
    '#4361ee': '#a78bfa',
    '#3a0ca3': '#9333ea',
    '#4cc9f0': '#fbbf24',
    '#f72585': '#fb7185',
    '#e74c3c': '#fb7185',
    '#ec7063': '#f472b6',
    '#2ecc71': '#34d399',
    '#4ade80': '#34d399',
    '#f39c12': '#fbbf24',
    '#fb923c': '#fbbf24',
    '#e67e22': '#fbbf24',
    '#f43f5e': '#f87171',
    
    # Backgrounds
    '#f8f9fa': '#faf5ff',
    '#f0f2f5': '#faf5ff',
    '#f5f7fb': '#faf5ff',
    '#f6f9fc': '#faf5ff',
    '#edf2f7': '#fef3c7',
    
    # Borders
    '#e2e8f0': 'rgba(167, 139, 250, 0.15)',
}

def update_file(filepath):
    """Update colors and styles in a file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original = content
        
        # Replace colors
        for old, new in REPLACEMENTS.items():
            content = content.replace(old, new)
        
        # Update gradients to include all three colors
        content = re.sub(
            r'linear-gradient\(to right,\s*var\(--primary-color\),\s*var\(--accent-color\)\)',
            'linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--accent-color))',
            content
        )
        
        # Update background gradients
        content = re.sub(
            r'background:\s*linear-gradient\(135deg,\s*#f[0-9a-f]{5}\s+0%,\s*#[0-9a-f]{6}\s+100%\)',
            'background: linear-gradient(135deg, #faf5ff 0%, #fef3c7 50%, #fce7f3 100%)',
            content
        )
        
        # Update box shadows
        content = re.sub(
            r'box-shadow:\s*0\s+[0-9]+px\s+[0-9]+px\s+-?[0-9]+px\s+rgba\(0,?\s*0,?\s*0,?\s*0\.[0-9]+\)',
            'box-shadow: 0 8px 24px rgba(167, 139, 250, 0.12)',
            content
        )
        
        # Update button shadows
        content = re.sub(
            r'box-shadow:\s*0\s+4px\s+10px\s+rgba\(67,?\s*97,?\s*238,?\s*0\.3\)',
            'box-shadow: 0 4px 12px rgba(167, 139, 250, 0.25)',
            content
        )
        
        if content != original:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            return True
        return False
    except Exception as e:
        print(f"Error: {e}")
        return False

updated = 0
for filename in FILES:
    filepath = os.path.join(BASE_DIR, filename)
    if os.path.exists(filepath):
        if update_file(filepath):
            print(f"✓ {filename}")
            updated += 1
        else:
            print(f"- {filename} (no changes)")
    else:
        print(f"✗ {filename} (not found)")

print(f"\n✅ Updated {updated}/{len(FILES)} files")
