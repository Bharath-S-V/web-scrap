<?php
require 'vendor/autoload.php'; // Ensure Composer's autoload is correctly included
require 'vendor/simplehtmldom/simplehtmldom/simple_html_dom.php'; // Adjust the path if needed
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Scraper</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            margin-top: 50px;
            margin-bottom: 50px;
        }

        h1 {
            color: #007bff;
            margin-bottom: 30px;
        }

        h2,
        h3 {
            color: #343a40;
        }

        .scraped-data {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        .img-fluid {
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        footer {
            margin-top: 40px;
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="text-center">Web Scraper</h1>
        <form method="POST" class="mb-4">
            <div class="input-group">
                <input type="url" name="url" class="form-control" placeholder="Enter URL to scrape" required>
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Scrape</button>
                </div>
            </div>
        </form>

        <?php
        if (isset($_POST['url']) || isset($_GET['url'])) {
            $url = isset($_POST['url']) ? $_POST['url'] : $_GET['url'];
            $url = filter_var($url, FILTER_SANITIZE_URL);

            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                echo '<div class="alert alert-danger">Invalid URL format.</div>';
                exit;
            }

            // Load the website's HTML using cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36');

            // Execute the request
            $htmlContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                echo '<div class="alert alert-danger">Failed to retrieve the website. HTTP Status Code: ' . $httpCode . '</div>';
                exit;
            }

            // Create a new simple_html_dom instance
            $html = str_get_html($htmlContent);

            $pageTitle = isset($html->find('title', 0)->innertext) ? $html->find('title', 0)->innertext : 'Not found';
            $metaDescription = isset($html->find('meta[name=description]', 0)->content) ? $html->find('meta[name=description]', 0)->content : 'Not found';
            $metaKeywords = isset($html->find('meta[name=keywords]', 0)->content) ? $html->find('meta[name=keywords]', 0)->content : 'Not found';
            $canonicalLink = isset($html->find('link[rel=canonical]', 0)->href) ? $html->find('link[rel=canonical]', 0)->href : 'Not found';
            $ogTitle = isset($html->find('meta[property=og:title]', 0)->content) ? $html->find('meta[property=og:title]', 0)->content : 'Not found';
            $ogDescription = isset($html->find('meta[property=og:description]', 0)->content) ? $html->find('meta[property=og:description]', 0)->content : 'Not found';

            // All Headings (h1, h2, h3...)
            $headings = [];
            foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $heading) {
                foreach ($html->find($heading) as $h) {
                    $headings[] = "$heading: " . $h->innertext;
                }
            }

            // Parse the domain name from the URL to handle relative image URLs
            $parsedUrl = parse_url($url);
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

            // All Image alt attributes with proper handling of relative URLs
            $images = [];
            foreach ($html->find('img') as $img) {
                $src = $img->src;

                // Check if the image URL is relative, and convert to absolute if necessary
                if (strpos($src, 'http') === false) {
                    $src = (strpos($src, '/') === 0 ? $baseUrl : $baseUrl . '/') . ltrim($src, '/');
                }

                $altText = $img->alt ?: 'No alt attribute';
                $images[] = "$src - $altText";
            }

            // Prepare CSV content
            $csvData = [
                ['Page Title', $pageTitle],
                ['Meta Description', $metaDescription],
                ['Meta Keywords', $metaKeywords],
                ['Canonical Link', $canonicalLink],
                ['OG Title', $ogTitle],
                ['OG Description', $ogDescription],
            ];

            foreach ($headings as $heading) {
                $csvData[] = ['Heading', $heading];
            }

            foreach ($images as $img) {
                $csvData[] = ['Image', $img];
            }

            // Create the CSV file and provide download link
            $csvFileName = 'scraped_data.csv';
            $file = fopen($csvFileName, 'w');

            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }

            fclose($file);

            // Display the scraped data
            echo "<div class='scraped-data'>";
            echo "<h2 class='mt-4'>Scraped Data from: $url</h2>";
            echo "<p><strong>Page Title:</strong> $pageTitle</p>";
            echo "<p><strong>Meta Description:</strong> $metaDescription</p>";
            echo "<p><strong>Meta Keywords:</strong> $metaKeywords</p>";
            echo "<p><strong>Canonical Link:</strong> $canonicalLink</p>";
            echo "<p><strong>OG Title:</strong> $ogTitle</p>";
            echo "<p><strong>OG Description:</strong> $ogDescription</p>";

            // Headings
            echo "<h3>Headings:</h3><ul class='list-group'>";
            foreach ($headings as $heading) {
                echo "<li class='list-group-item'>$heading</li>";
            }
            echo "</ul>";

            // Images with alt text
            echo "<h3>Images:</h3><div class='row'>";
            foreach ($images as $img) {
                list($src, $altText) = explode(' - ', $img);
                echo "<div class='col-md-4 mb-4'>
                    <img src='$src' alt='$altText' class='img-fluid'>
                    <p><strong>Alt:</strong> $altText</p>
                  </div>";
            }
            echo "</div>";

            // Download Link for CSV Content
            echo "<h3>Download Scraped Data as CSV:</h3>";
            echo "<a href='$csvFileName' download='$csvFileName' class='btn btn-success'>Download CSV</a>";
            echo "</div>";
        } else {
            echo '<div class="alert alert-warning">No URL provided.</div>';
        }
        ?>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Web Scraper. All Rights Reserved.</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>