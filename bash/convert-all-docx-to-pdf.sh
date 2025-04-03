#!/bin/bash

read -p "Enter the directory containing the DOCX files: " input_dir
read -p "Enter the directory to store the PDF files: " output_dir

mkdir -p "$output_dir"

for docx_file in "$input_dir"/*.docx; do
  if [ -f "$docx_file" ]; then
    echo "Converting $docx_file to PDF..."
    libreoffice --headless --convert-to pdf --outdir "$output_dir" "$docx_file"

    if [ $? -eq 0 ]; then
      echo "Conversion successful, removing $docx_file..."
      rm "$docx_file"
    else
      echo "Failed to convert $docx_file."
    fi
  fi
done

echo "All conversions completed."
