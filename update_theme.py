"""
Script to apply light aesthetic theme to all PHP pages
This will update CSS color variables across all pages for consistency
"""

import re
import os

# Define the new light theme color palette
OLD_COLORS = {
    '#2c3e50': '#a78bfa',  # primary-color
    '#34495e': '#c4b5fd',  # primary-light
    '#3498db': '#fbbf24',  # secondary-color
    '#5dade2': '#fbbf24',  # secondary-light (amber)
    '#e74c3c': '#fb7185',  # accent-color
    '#2ecc71': '#34d399',  # success-color
    '#f39c12': '#fbbf24',  # warning-color
    '#4a90e2': '#a78bfa',  # alternative primary
    '#4776E6': '#a78bfa',  # another primary variant
    '#8E54E9': '#c4b5fd',  # another variant
}

# Background colors
BG_UPDATES = {
    '#f8f9fa': '#faf5ff',
    '#f0f2f5': '#faf5ff',
    '#f5f7fb': '#faf5ff',
}

# Files to update (excluding already updated ones)
FILES_TO_UPDATE = [
    'view_attendancet.php',
    'manage_batches.php',
    'manage_students.php',
    'manage_teachers.php',
    'my_batches.php',
    'my_subjects.php',
    'report_attendance.php',
    'todays_sessions.php',
    'add_notification.php',
    'edit_attendance.php',
    'change_passworda.php',
    'change_passwords.php',
    'change_passwordt.php',
]

def update_colors_in_file(filepath):
    """Update color codes in a PHP file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        
        # Update color codes
        for old_color, new_color in OLD_COLORS.items():
            content = content.replace(old_color, new_color)
        
        # Update background colors
        for old_bg, new_bg in BG_UPDATES.items():
            content = content.replace(old_bg, new_bg)
        
        # Update border-radius values
        content = re.sub(r'border-radius:\s*8px', 'border-radius: 16px', content)
        content = re.sub(r'border-radius:\s*10px', 'border-radius: 16px', content)
        
        # Update box-shadow for softer look
        content = re.sub(
            r'box-shadow:\s*0\s+2px\s+4px\s+rgba\(0,\s*0,\s*0,\s*0\.1\)',
            'box-shadow: 0 8px 24px rgba(167, 139, 250, 0.12)',
            content
        )
        
        if content != original_content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            return True
        return False
    except Exception as e:
        print(f"Error updating {filepath}: {e}")
        return False

# Main execution
if __name__ == "__main__":
    base_dir = "d:/NOYEL/attendance/attendance_controller_BACKUP"
    updated_count = 0
    
    for filename in FILES_TO_UPDATE:
        filepath = os.path.join(base_dir, filename)
        if os.path.exists(filepath):
            if update_colors_in_file(filepath):
                print(f"✓ Updated: {filename}")
                updated_count += 1
            else:
                print(f"- No changes: {filename}")
        else:
            print(f"✗ Not found: {filename}")
    
    print(f"\nTotal files updated: {updated_count}/{len(FILES_TO_UPDATE)}")
