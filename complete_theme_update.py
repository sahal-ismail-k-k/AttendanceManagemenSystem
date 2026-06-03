"""
Comprehensive theme updater - Replace ALL old color codes with new light aesthetic
"""
import os
import re

# Base directory
BASE_DIR = r"d:\NOYEL\attendance\attendance_controller_BACKUP"

# Comprehensive color mapping
COLOR_MAP = {
    # Old dark colors -> New light purple
    '#2c3e50': '#a78bfa',
    '#34495e': '#c4b5fd',
    '#2C3E50': '#a78bfa',
    '#34495E': '#c4b5fd',
    
    # Old blue colors -> New amber/purple
    '#3498db': '#fbbf24',
    '#5dade2': '#fbbf24',
    '#4a90e2': '#a78bfa',
    '#4895ef': '#c4b5fd',
    '#4361ee': '#a78bfa',
    '#3A0CA3': '#9333ea',
    '#4CC9F0': '#fbbf24',
    
    # Old red -> New pink
    '#e74c3c': '#fb7185',
    '#ec7063': '#f472b6',
    '#F72585': '#fb7185',
    '#f43f5e': '#f87171',
    
    # Old green -> New emerald
    '#2ecc71': '#34d399',
    '#4ade80': '#34d399',
    
    # Old orange/yellow -> New amber
    '#f39c12': '#fbbf24',
    '#fb923c': '#fbbf24',
    '#e67e22': '#fbbf24',
    
    # Background colors
    '#f8f9fa': '#faf5ff',
    '#f0f2f5': '#faf5ff',
    '#f5f7fb': '#faf5ff',
    '#F6F9FC': '#faf5ff',
    '#EDF2F7': '#fef3c7',
    
    # Border colors
    '#e2e8f0': 'rgba(167, 139, 250, 0.15)',
}

def update_file(filepath):
    """Update a single file with new colors"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original = content
        
        # Replace all color codes
        for old_color, new_color in COLOR_MAP.items():
            content = content.replace(old_color, new_color)
        
        # Update border-radius
        content = re.sub(r'border-radius:\s*8px', 'border-radius: 16px', content, flags=re.IGNORECASE)
        content = re.sub(r'border-radius:\s*10px', 'border-radius: 16px', content, flags=re.IGNORECASE)
        content = re.sub(r'border-radius:\s*12px', 'border-radius: 16px', content, flags=re.IGNORECASE)
        
        # Update box shadows to use purple tint
        content = re.sub(
            r'box-shadow:\s*0\s+2px\s+4px\s+rgba\(0,?\s*0,?\s*0,?\s*0\.1\)',
            'box-shadow: 0 8px 24px rgba(167, 139, 250, 0.12)',
            content,
            flags=re.IGNORECASE
        )
        content = re.sub(
            r'box-shadow:\s*0\s+4px\s+12px\s+rgba\(0,?\s*0,?\s*0,?\s*0\.08\)',
            'box-shadow: 0 8px 24px rgba(167, 139, 250, 0.12)',
            content,
            flags=re.IGNORECASE
        )
        
        if content != original:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            return True
        return False
    except Exception as e:
        print(f"Error: {filepath} - {e}")
        return False

# Get all PHP files
php_files = []
for root, dirs, files in os.walk(BASE_DIR):
    # Skip certain directories
    if 'uploads' in root or 'vendor' in root or 'node_modules' in root:
        continue
    for file in files:
        if file.endswith('.php'):
            php_files.append(os.path.join(root, file))

print(f"Found {len(php_files)} PHP files")
print("Updating...")

updated = 0
for filepath in php_files:
    if update_file(filepath):
        filename = os.path.basename(filepath)
        print(f"✓ {filename}")
        updated += 1

print(f"\n✅ Updated {updated}/{len(php_files)} files")
