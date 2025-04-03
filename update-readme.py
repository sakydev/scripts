import os

# Allowed file extensions
allowed_extensions = {".php", ".py", ".go", ".sh", ".bat", ".js"}

# Use the current directory as the repo directory
repo_dir = "."

# Create a README.md file or open it if it already exists
readme_path = os.path.join(repo_dir, "README.md")

# Open README.md for writing (overwrites existing file)
with open(readme_path, 'w') as readme_file:
    # Write a header for the README
    readme_file.write("# Helper Scripts\n\n")
    readme_file.write("Why do boring stuff yourself, eh? These are some helper scripts to save you time.\n\n")

    # Iterate over each subdirectory in the current directory
    for folder in os.listdir(repo_dir):
        folder_path = os.path.join(repo_dir, folder)

        # Skip if not a directory
        if not os.path.isdir(folder_path):
            continue

        # Iterate over all files in the folder
        for file in os.listdir(folder_path):
            file_path = os.path.join(folder_path, file)

            # Get file extension and check if it's in the allowed list
            if os.path.isfile(file_path) and os.path.splitext(file)[1] in allowed_extensions:
                # Convert file name to title case (replace underscores/dashes with spaces)
                title = os.path.splitext(file)[0].replace("_", " ").replace("-", " ").title()

                # Create a markdown link for each file
                file_link = os.path.relpath(file_path, repo_dir)
                readme_file.write(f"- [{title}]({file_link})\n")

print("Links have been added to README.md")
