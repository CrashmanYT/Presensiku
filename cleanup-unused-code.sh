#!/bin/bash

echo "🧹 Starting cleanup of unused code..."

# Create backup directory
mkdir -p backup_$(date +%Y%m%d_%H%M%S)

# Function to safely remove file if it exists
safe_remove() {
    if [ -f "$1" ]; then
        echo "Removing: $1"
        rm "$1"
    else
        echo "File not found: $1"
    fi
}

# Function to safely remove directory if it exists
safe_remove_dir() {
    if [ -d "$1" ]; then
        echo "Removing directory: $1"
        rm -rf "$1"
    else
        echo "Directory not found: $1"
    fi
}

echo "📁 Removing unused Livewire Auth components..."
safe_remove "app/Livewire/Auth/ConfirmPassword.php"
safe_remove "app/Livewire/Auth/ForgotPassword.php"
safe_remove "app/Livewire/Auth/Login.php"
safe_remove "app/Livewire/Auth/Register.php"
safe_remove "app/Livewire/Auth/ResetPassword.php"
safe_remove "app/Livewire/Auth/VerifyEmail.php"
safe_remove "app/Livewire/Actions/Logout.php"
safe_remove_dir "app/Livewire/Auth"

echo "📁 Removing unused Settings components..."
safe_remove "app/Livewire/Settings/Appearance.php"
safe_remove "app/Livewire/Settings/DeleteUserForm.php"
safe_remove "app/Livewire/Settings/Password.php"
safe_remove "app/Livewire/Settings/Profile.php"
safe_remove_dir "app/Livewire/Settings"

echo "📁 Removing unused Jobs..."
safe_remove "app/Jobs/FetchFingerspotDeviceInfo.php"
safe_remove "app/Jobs/ProcessFingerprintScan.php"

echo "📁 Removing unused Interface..."
safe_remove "app/Interfaces/AttendanceInterface.php"
safe_remove_dir "app/Interfaces"


echo "📁 Removing unused Views..."
safe_remove_dir "resources/views/components/layouts/app"
safe_remove "resources/views/components/layouts/auth.blade.php"
safe_remove_dir "resources/views/components/layouts/auth"
safe_remove_dir "resources/views/components/settings"
safe_remove_dir "resources/views/livewire/auth"
safe_remove_dir "resources/views/livewire/settings"
safe_remove "resources/views/dashboard.blade.php"
safe_remove "resources/views/welcome.blade.php"




echo "📁 Removing Test Files..."
safe_remove_dir "tests/Feature/Auth"
safe_remove_dir "tests/Feature/Settings"
safe_remove "tests/Feature/DashboardTest.php"

echo "📁 Removing unused Factories..."
safe_remove "database/factories/DisciplineRankingFactory.php"
safe_remove "database/factories/HolidayFactory.php"

echo "📁 Removing unused Seeders..."
safe_remove "database/seeders/HolidaySeeder.php"

echo "✅ Cleanup completed!"
echo "⚠️  Remember to:"
echo "   1. Update routes/auth.php to remove unused routes"
echo "   2. Update routes/web.php to remove unused routes"
echo "   3. Update database migrations if needed"
echo "   4. Update composer.json dependencies"
echo "   5. Run 'composer dump-autoload'"
echo "   6. Test your application thoroughly"
