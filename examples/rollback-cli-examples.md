# AutoGen Rollback System - CLI Examples

This document provides comprehensive examples of using the AutoGen rollback system through the command line interface.

## Basic Rollback Operations

### List Available Backups

```bash
# List all available backups
php artisan autogen:backup:list

# Expected output:
# Available rollback operations:
# 
# +------------------+---------------------+-------------+--------+-------------+------------------+
# | Operation ID     | Created At          | Strategies  | Size   | Can Restore | Context          |
# +------------------+---------------------+-------------+--------+-------------+------------------+
# | model_2024_001   | 2024-01-15 14:30:00 | file,db     | 15.2KB | ✓           | Model: User      |
# | ctrl_2024_002    | 2024-01-15 13:45:00 | file        | 8.5KB  | ✓           | Controller: Post |
# +------------------+---------------------+-------------+--------+-------------+------------------+
```

### Detailed Backup Information

```bash
# Show detailed information about all backups
php artisan autogen:backup:list --detailed

# Expected output:
# Operation: model_2024_001
# Created: 2024-01-15 14:30:00
# Context: Model: User, Table: users
# Restorable: Yes
# 
# Strategies:
# +----------+----------------------+--------+-------------+
# | Strategy | Backup ID           | Size   | Restorable  |
# +----------+----------------------+--------+-------------+
# | file     | backup_2024_001_abc  | 10.2KB | ✓           |
# | database | db_backup_2024_001   | 5.0KB  | ✓           |
# +----------+----------------------+--------+-------------+
```

### Filter Backups by Strategy

```bash
# Show only file backup operations
php artisan autogen:backup:list --strategy=file_backup

# Show only database backup operations  
php artisan autogen:backup:list --strategy=database_rollback
```

### Export Backup List as JSON

```bash
# Export all backups as JSON
php artisan autogen:backup:list --format=json > backups.json

# Example JSON output:
# {
#     "total_backups": 2,
#     "total_size": 23780,
#     "backups": [
#         {
#             "operation_id": "model_2024_001",
#             "created_at": "2024-01-15T14:30:00Z",
#             "context": {
#                 "model_name": "User",
#                 "table_name": "users"
#             },
#             "total_size": 15552,
#             "can_restore": true,
#             "strategies": {
#                 "file_backup": {
#                     "backup_id": "backup_2024_001_abc",
#                     "size": 10450,
#                     "can_restore": true
#                 }
#             }
#         }
#     ]
# }
```

## Rollback Operations

### Interactive Rollback Selection

```bash
# Start interactive rollback process
php artisan autogen:rollback

# Expected interaction:
# Select operation to rollback:
#   [0] model_2024_001 (2024-01-15 14:30:00) - Model: User, Table: users
#   [1] ctrl_2024_002 (2024-01-15 13:45:00) - Controller: PostController
# > 0
# 
# Operation Details:
#   ID: model_2024_001
#   Created: 2024-01-15 14:30:00
#   Context: Model: User, Table: users
#   Strategies: file_backup, database_rollback
# 
# WARNING: This operation will restore files and database to their previous state.
# Any changes made after the backup was created will be lost.
# 
# Are you sure you want to rollback operation 'model_2024_001'? (yes/no) [no]:
# > yes
# 
# Starting rollback for operation: model_2024_001
# 
#  Preparing rollback... ✓
#  Restoring from backups... ✓
#  Rollback completed ✓
# 
# ✓ Rollback completed successfully for operation: model_2024_001
```

### Rollback Specific Operation

```bash
# Rollback specific operation with confirmation
php artisan autogen:rollback model_2024_001

# Rollback without confirmation prompt
php artisan autogen:rollback model_2024_001 --confirm
```

### Rollback Latest Operation

```bash
# Rollback the most recent operation
php artisan autogen:rollback --latest

# Rollback latest without confirmation
php artisan autogen:rollback --latest --confirm
```

### Rollback with Strategy Selection

```bash
# Use specific strategy for rollback (if supported)
php artisan autogen:rollback model_2024_001 --strategy=file_backup
```

## Backup Cleanup Operations

### Preview Cleanup (Dry Run)

```bash
# See what would be cleaned without actually deleting
php artisan autogen:backup:clean --days=30 --dry-run

# Expected output:
# Cleaning backups older than 30 days...
# DRY RUN MODE - No files will be actually deleted
# 
# Backups that would be cleaned:
# +------------------+---------------------+------------+-------------+--------+
# | Operation ID     | Created At          | Age (days) | Strategies  | Size   |
# +------------------+---------------------+------------+-------------+--------+
# | old_model_001    | 2023-12-01 10:00:00 | 45         | file,db     | 25.1KB |
# | old_ctrl_002     | 2023-12-05 15:30:00 | 41         | file        | 12.3KB |
# +------------------+---------------------+------------+-------------+--------+
# 
# Total backups to clean: 2
# Total space to free: 37.4KB
```

### Perform Cleanup

```bash
# Clean backups older than 30 days
php artisan autogen:backup:clean --days=30

# Expected interaction:
# Found 2 backup(s) to clean
# Total space to free: 37.4KB
# 
# WARNING: This operation will permanently delete backup files.
# Deleted backups cannot be recovered.
# 
# Are you sure you want to delete 2 backup(s)? (yes/no) [no]:
# > yes
# 
#  Cleaning old_model_001... ✓
#  Cleaning old_ctrl_002... ✓
# 
# ✓ Cleaned 2 backup(s)
```

### Force Cleanup Without Confirmation

```bash
# Clean without confirmation prompts
php artisan autogen:backup:clean --days=7 --force
```

### Clean Specific Strategy Only

```bash
# Clean only file backups
php artisan autogen:backup:clean --days=30 --strategy=file_backup

# Clean only database backups
php artisan autogen:backup:clean --days=30 --strategy=database_rollback
```

### Aggressive Cleanup

```bash
# Clean all backups older than 1 day (be careful!)
php artisan autogen:backup:clean --days=1 --force

# Clean all backups (use with extreme caution!)
php artisan autogen:backup:clean --days=0 --force
```

## Advanced Usage Examples

### Batch Operations

```bash
# Create backup before batch operations
php artisan autogen:backup:create \
    --operation="batch_migration_$(date +%s)" \
    --context='{"operation_type": "batch_migration", "models": ["User", "Post", "Comment"]}'

# Run your batch generation commands...
# php artisan autogen:generate:model User
# php artisan autogen:generate:model Post  
# php artisan autogen:generate:model Comment

# Check if rollback is needed
php artisan autogen:backup:list --latest

# Rollback entire batch if needed
php artisan autogen:rollback --latest --confirm
```

### CI/CD Integration

```bash
#!/bin/bash
# deployment-with-rollback.sh

echo "Creating pre-deployment backup..."
BACKUP_ID="deploy_$(date +%s)"
php artisan autogen:backup:create --operation="$BACKUP_ID"

echo "Running code generation..."
if php artisan autogen:generate:full-crud Order; then
    echo "✓ Code generation successful"
    
    # Run tests to verify
    if php artisan test; then
        echo "✓ Tests passed - deployment successful"
        
        # Optional: Clean old backups after successful deployment
        php artisan autogen:backup:clean --days=7 --force
    else
        echo "✗ Tests failed - rolling back..."
        php artisan autogen:rollback "$BACKUP_ID" --confirm
        exit 1
    fi
else
    echo "✗ Code generation failed - rolling back..."
    php artisan autogen:rollback "$BACKUP_ID" --confirm
    exit 1
fi
```

### Monitoring and Alerting

```bash
#!/bin/bash
# backup-health-check.sh

echo "Checking backup system health..."

# Get backup statistics
STATS=$(php artisan autogen:backup:list --format=json)
TOTAL_BACKUPS=$(echo "$STATS" | jq '.total_backups')
TOTAL_SIZE=$(echo "$STATS" | jq '.total_size')

echo "Total backups: $TOTAL_BACKUPS"
echo "Total size: $TOTAL_SIZE bytes"

# Check for failed backups (example logic)
FAILED_BACKUPS=$(php artisan autogen:backup:list --format=json | jq '[.backups[] | select(.can_restore == false)] | length')

if [ "$FAILED_BACKUPS" -gt 0 ]; then
    echo "⚠️  Warning: $FAILED_BACKUPS backups cannot be restored"
    
    # Send alert (example using curl to Slack webhook)
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"AutoGen Backup Alert: $FAILED_BACKUPS backups cannot be restored\"}" \
        "$SLACK_WEBHOOK_URL"
fi

# Check disk usage
BACKUP_SIZE_MB=$((TOTAL_SIZE / 1024 / 1024))
if [ "$BACKUP_SIZE_MB" -gt 1000 ]; then
    echo "⚠️  Warning: Backup storage using ${BACKUP_SIZE_MB}MB"
    
    # Suggest cleanup
    echo "Consider running: php artisan autogen:backup:clean --days=30"
fi

echo "Health check completed"
```

### Development Workflow

```bash
#!/bin/bash
# dev-safe-refactor.sh

FEATURE_NAME="user-profile-refactor"
BACKUP_ID="${FEATURE_NAME}_$(date +%s)"

echo "Starting safe refactoring workflow for: $FEATURE_NAME"

# Step 1: Create comprehensive backup
echo "Creating backup..."
php artisan autogen:backup:create --operation="$BACKUP_ID" \
    --context="{\"feature\": \"$FEATURE_NAME\", \"type\": \"refactor\"}"

# Step 2: Perform refactoring operations
echo "Performing refactoring..."

if php artisan autogen:generate:model UserProfile --enhanced; then
    if php artisan autogen:generate:controller UserProfileController --api; then
        if php artisan autogen:generate:migration update_user_profiles; then
            echo "✓ All generation steps completed"
            
            # Step 3: Run tests
            echo "Running tests..."
            if php artisan test --filter=UserProfile; then
                echo "✓ Refactoring completed successfully"
                echo "Backup available if needed: $BACKUP_ID"
            else
                echo "✗ Tests failed - rolling back refactoring..."
                php artisan autogen:rollback "$BACKUP_ID" --confirm
                exit 1
            fi
        else
            echo "✗ Migration generation failed - rolling back..."
            php artisan autogen:rollback "$BACKUP_ID" --confirm
            exit 1
        fi
    else
        echo "✗ Controller generation failed - rolling back..."
        php artisan autogen:rollback "$BACKUP_ID" --confirm
        exit 1
    fi
else
    echo "✗ Model generation failed - rolling back..."
    php artisan autogen:rollback "$BACKUP_ID" --confirm
    exit 1
fi
```

### Maintenance Tasks

```bash
# Daily maintenance script
#!/bin/bash
# daily-backup-maintenance.sh

echo "Starting daily backup maintenance..."

# Clean old backups (keep 30 days)
echo "Cleaning old backups..."
php artisan autogen:backup:clean --days=30 --force

# Check for corrupted backups
echo "Validating backup integrity..."
php artisan autogen:backup:validate --all --quiet

# Generate backup report
echo "Generating backup report..."
php artisan autogen:backup:list --format=json > "backup-report-$(date +%Y%m%d).json"

# Compress old backup reports
find . -name "backup-report-*.json" -mtime +7 -exec gzip {} \;

echo "Maintenance completed"
```

## Error Scenarios and Recovery

### Handle Partial Failures

```bash
# When generation partially fails
php artisan autogen:generate:crud Product

# If it fails partway through:
# ✓ Model generated
# ✓ Migration generated  
# ✗ Controller generation failed
# 
# Check what can be rolled back:
php artisan autogen:backup:list --latest

# Rollback the partial operation:
php artisan autogen:rollback --latest --confirm
```

### Recovery from Storage Issues

```bash
# If backup storage has issues
php artisan autogen:backup:list

# Error: Unable to read backup index
# 
# Try to recover:
php artisan autogen:backup:repair --rebuild-index

# If backups are corrupted:
php artisan autogen:backup:validate --all
php artisan autogen:backup:repair --fix-corrupted
```

### Emergency Recovery

```bash
# Nuclear option - rollback everything to a specific point
php artisan autogen:rollback:emergency \
    --before="2024-01-15 14:00:00" \
    --confirm-emergency \
    --create-checkpoint

# This would:
# 1. Create a checkpoint of current state
# 2. Find all operations after the specified time
# 3. Rollback them in reverse chronological order
# 4. Provide a summary of what was rolled back
```

## Tips and Best Practices

### Before Major Operations

```bash
# Always create a manual backup before major changes
php artisan autogen:backup:create \
    --operation="before_major_refactor" \
    --description="Before implementing new user system"

# Verify the backup was created successfully
php artisan autogen:backup:list --latest
```

### Regular Monitoring

```bash
# Add to crontab for daily backup monitoring
0 9 * * * cd /path/to/project && php artisan autogen:backup:list --format=json | jq '.total_backups' | mail -s "Daily Backup Count" admin@example.com
```

### Performance Optimization

```bash
# For large codebases, use compression
php artisan autogen:backup:create --compress --operation="large_project_backup"

# Clean aggressively in development
php artisan autogen:backup:clean --days=1 --force # Development only!

# Keep longer retention in production  
php artisan autogen:backup:clean --days=90 --force # Production
```

These examples demonstrate the comprehensive CLI capabilities of the AutoGen rollback system, providing safe and reliable code generation with complete recovery options.