# Book Cover Upload Feature Implementation

## Overview
Successfully implemented image upload functionality for book covers in the Books CRUD system for both **Admin** and **Librarian** roles. Users can now upload cover images from their local storage instead of entering manual file paths.

## Features Implemented

### 1. **File Upload UI**
- Modern file input with drag-and-drop styling
- Image preview functionality (shows thumbnail before upload)
- Remove button to clear selected image
- File name display
- Format and size guidelines (JPG, PNG, GIF, WebP - Max 2MB)

### 2. **Backend Processing**
- Automatic file storage in `storage/app/public/books/covers/`
- File validation (type and size)
- Old file deletion when updating or removing covers
- Proper cleanup when deleting books

### 3. **Public Access**
- Storage link created (`public/storage` → `storage/app/public`)
- Images accessible via `/storage/books/covers/filename.jpg`

## Files Modified

### Frontend Components

#### 1. `resources/js/Components/Books/BookForm.tsx`
**Changes:**
- Updated `cover` field type from `string` to `File | string | null`
- Added state management for cover preview and file name
- Added `handleCoverChange()` function for file selection
- Added `removeCover()` function to clear selection
- Replaced text input with file input (`type="file"`)
- Added image preview with remove button
- Added file format and size guidelines

**Key Features:**
```typescript
// File input with preview
<input
    type="file"
    accept="image/*"
    onChange={handleCoverChange}
    className="w-full text-sm file:mr-4 file:rounded-lg..."
/>

// Preview display
{coverPreview && (
    <div className="relative">
        <img src={...} alt="Cover preview" className="h-24 w-16..." />
        <button onClick={removeCover}>×</button>
    </div>
)}
```

#### 2. `resources/js/Pages/Admin/Books/Create.tsx`
**Changes:**
- Updated submit to use `forceFormData: true` for file upload
- Added `preserveScroll: true` for better UX

#### 3. `resources/js/Pages/Admin/Books/Edit.tsx`
**Changes:**
- Updated submit to use `forceFormData: true`
- Existing cover path passed to form for preview

#### 4. `resources/js/Pages/Librarian/Books/Create.tsx`
**Changes:**
- Same as Admin Create page

#### 5. `resources/js/Pages/Librarian/Books/Edit.tsx`
**Changes:**
- Same as Admin Edit page

### Backend Files

#### 1. `app/Http/Requests/StoreBookRequest.php`
**Changes:**
- Updated `cover` validation rule:
  ```php
  'cover' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048']
  ```
- Updated authorization to include Librarian role:
  ```php
  return $this->user()?->hasAnyRole(['Super Admin', 'Librarian']) ?? false;
  ```

#### 2. `app/Http/Requests/UpdateBookRequest.php`
**Changes:**
- Updated `cover` validation rule (same as StoreBookRequest)
- Updated authorization to include Librarian role

#### 3. `app/Services/BookService.php`
**Changes:**
- Added imports: `Storage`, `UploadedFile`
- Added `handleCoverUpload()` method for file processing
- Updated `createBook()` to handle cover upload
- Updated `updateBook()` to handle cover upload and old file deletion
- Updated `deleteBook()` to delete cover file when book is deleted

**Key Method:**
```php
protected function handleCoverUpload(array $data, ?string $existingCover = null): array
{
    if (isset($data['cover']) && $data['cover'] instanceof UploadedFile) {
        // Delete old cover if exists
        if ($existingCover) {
            Storage::disk('public')->delete($existingCover);
        }
        
        // Store new cover
        $path = $data['cover']->store('books/covers', 'public');
        $data['cover'] = $path;
    } elseif (!isset($data['cover']) || $data['cover'] === null) {
        // Remove existing cover
        if ($existingCover) {
            Storage::disk('public')->delete($existingCover);
        }
        $data['cover'] = null;
    }
    
    return $data;
}
```

### Storage Configuration

#### Storage Link Created
```bash
php artisan storage:link
```
**Result:**
- Created symlink: `public/storage` → `storage/app/public`
- Files stored in: `storage/app/public/books/covers/`
- Public URL: `http://yoursite.com/storage/books/covers/filename.jpg`

## How It Works

### Creating a Book with Cover

1. **User Action:**
   - Navigate to Add Book page
   - Fill in book details
   - Click "Choose File" or drag file to file input
   - Select image from local storage
   - Preview appears automatically
   - Click "Save Book"

2. **Frontend Processing:**
   - File selected triggers `handleCoverChange()`
   - FileReader creates preview URL
   - Form data includes File object
   - Submit with `forceFormData: true` converts to multipart/form-data

3. **Backend Processing:**
   - Request validation checks file type and size
   - BookService receives data with UploadedFile
   - `handleCoverUpload()` stores file to `books/covers` directory
   - File path saved to database (e.g., `books/covers/abc123.jpg`)
   - Book record created with cover path

### Updating a Book Cover

1. **User Action:**
   - Navigate to Edit Book page
   - Existing cover preview shown
   - Click to change file
   - Select new image
   - Old preview replaced with new preview
   - Click "Update Book"

2. **Backend Processing:**
   - New file uploaded
   - Old cover file deleted from storage
   - New file stored
   - Database updated with new path

### Removing a Book Cover

1. **User Action:**
   - On Create/Edit page
   - Click × button on preview
   - Preview disappears
   - Submit form

2. **Backend Processing:**
   - Cover field set to null
   - Old cover file deleted
   - Database updated with null value

### Deleting a Book

1. **User Action:**
   - Click Delete button
   - Confirm deletion

2. **Backend Processing:**
   - BookService checks for existing cover
   - Cover file deleted from storage
   - Book record deleted from database

## Validation Rules

### File Type
- ✅ JPEG/JPG
- ✅ PNG
- ✅ GIF
- ✅ WebP

### File Size
- Maximum: **2MB** (2048 KB)

### Error Messages
Laravel will automatically return validation errors if:
- File is not an image
- File format not supported
- File exceeds 2MB limit

## Storage Structure

```
storage/
└── app/
    └── public/
        └── books/
            └── covers/
                ├── abc123def.jpg
                ├── xyz789ghi.png
                └── ...
```

## Public Access URL Pattern

```
http://yoursite.com/storage/books/covers/{filename}
```

Example:
```
http://localhost:8000/storage/books/covers/abc123def.jpg
```

## Benefits

1. **User-Friendly:** Visual preview before upload
2. **Flexible:** Support for multiple image formats
3. **Safe:** File validation prevents invalid uploads
4. **Clean:** Automatic cleanup of old/unused files
5. **Efficient:** Files stored in optimized location
6. **Accessible:** Public storage link for easy access
7. **Role-Based:** Both Admin and Librarian can upload

## Usage Examples

### Admin Role
1. Go to `/admin/books`
2. Click "Add Book"
3. Fill form and upload cover
4. Book appears with cover in catalog

### Librarian Role
1. Go to `/librarian/books`
2. Click "Add Book"
3. Same upload process as Admin
4. Identical functionality

## Testing Checklist

- [x] File upload on Create page (Admin)
- [x] File upload on Create page (Librarian)
- [x] File upload on Edit page (Admin)
- [x] File upload on Edit page (Librarian)
- [x] Image preview works
- [x] Remove cover button works
- [x] File validation (type and size)
- [x] Old file deletion on update
- [x] File deletion when book deleted
- [x] Public access to uploaded files
- [x] Storage link created

## Troubleshooting

### Issue: Upload fails with permission error
**Solution:** Ensure `storage/app/public` has write permissions
```bash
chmod -R 775 storage/app/public
```

### Issue: Images not showing after upload
**Solution:** Verify storage link exists
```bash
php artisan storage:link
```

### Issue: File too large error
**Solution:** Check PHP upload limits in `php.ini`
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

### Issue: Validation error on file type
**Solution:** Ensure file is one of: JPG, PNG, GIF, WebP

## Future Enhancements (Optional)

- Image compression before upload
- Drag and drop upload zone
- Multiple image uploads (gallery)
- Image cropping functionality
- CDN integration for faster delivery
- Thumbnail generation
- Default cover image when none uploaded
- EXIF data removal for privacy

## Notes

- Files are stored with unique auto-generated names to prevent conflicts
- Original filenames are not preserved (security best practice)
- Cover field in database stores relative path, not full URL
- When editing, existing cover path is converted to preview URL
- File input resets after successful submission
- FormData conversion is handled by Inertia's `forceFormData` option
