#!/bin/bash

# Ask for input directory
read -p "Enter the input directory: " input_dir

# Ask for output directory
read -p "Enter the output directory: " output_dir

# Create output directory if it does not exist
mkdir -p "$output_dir"

# Iterate over each PDF file in the input directory
for pdf_file in "$input_dir"/*.pdf; do
    # Extract filename without extension
    filename=$(basename -- "$pdf_file" .pdf)

    # Convert PDF to text and save in output directory
    pdftotext "$pdf_file" "$output_dir/$filename.txt"

    echo "Processed: $pdf_file -> $output_dir/$filename.txt"
done

echo "All PDFs have been processed!"
