<?php
include __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../includes/adviser_assignments.php';

function uniqueOptions(array $options): array {
    $clean = [];
    foreach ($options as $opt) {
        $opt = trim($opt);
        if ($opt === '') {
            continue;
        }
        if (!in_array($opt, $clean, true)) {
            $clean[] = $opt;
        }
    }
    return $clean;
}

function normalizeEarlyYear(string $year): string {
    $year = trim($year);
    if (in_array($year, ['Kinder 1', 'Kinder 2'], true)) {
        return 'Kindergarten';
    }
    return $year;
}

function sectionSuggestions(mysqli $conn, string $year, string $strand): array {
    $year = normalizeEarlyYear($year);
    $strand = trim($strand);
    $suggestions = [];

    if (in_array($year, ['Pre-Prime 1', 'Pre-Prime 2', 'Kindergarten'], true)) {
        $suggestions = ['Hershey', 'Kisses'];
    } elseif (in_array($year, ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'], true)) {
        $suggestions = ['Section A', 'Section B'];
    } elseif (in_array($year, ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'], true)) {
        $suggestions = ['Section A', 'Section B'];
    } elseif (in_array($year, ['Grade 11', 'Grade 12'], true)) {
        if ($strand && $strand !== 'N/A') {
            $suggestions = [
                $strand . ' - Section 1',
                $strand . ' - Section 2'
            ];
        } else {
            $suggestions = ['Section 1', 'Section 2'];
        }
    }

    $dbSections = adviser_assignments_sections_for_grade($conn, $year);
    if (!empty($dbSections)) {
        $suggestions = array_merge($suggestions, $dbSections);
    }

    return uniqueOptions($suggestions);
}

function adviserSuggestions(mysqli $conn, string $year, string $strand): array {
    $year = normalizeEarlyYear($year);
    $strand = trim($strand);
    $suggestions = adviser_assignments_adviser_options($conn, $year);

    if (empty($suggestions) && in_array($year, ['Grade 11', 'Grade 12'], true) && $strand !== '') {
        $strandSection = $strand . ' - Section 1';
        $adviser = adviser_assignments_adviser_for_section($conn, $year, $strandSection);
        if ($adviser !== null && $adviser !== '') {
            $suggestions[] = $adviser;
        }
    }

    $suggestions[] = 'To be assigned';

    return uniqueOptions($suggestions);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);

    $sectionSelect = $_POST['section_select'] ?? null;
    $sectionCustom = trim($_POST['section_custom'] ?? '');
    $adviserSelect = $_POST['adviser_select'] ?? null;
    $adviserCustom = trim($_POST['adviser_custom'] ?? '');

    if ($sectionSelect !== null) {
        $newSection = ($sectionSelect === '__custom' || $sectionSelect === '') ? $sectionCustom : trim($sectionSelect);
    } else {
        $newSection = trim($_POST['section'] ?? '');
    }

    if ($adviserSelect !== null) {
        $newAdviser = ($adviserSelect === '__custom') ? $adviserCustom : trim($adviserSelect);
    } else {
        $newAdviser = trim($_POST['adviser'] ?? '');
    }

    if ($id === 0 || $newSection === '') {
        echo "<script>alert('Please provide the required information.'); window.history.back();</script>";
        exit();
    }

    $stmt = $conn->prepare("UPDATE students_registration SET section = ?, adviser = ? WHERE id = ?");
    $stmt->bind_param('ssi', $newSection, $newAdviser, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Section information updated successfully.'); window.location.href='registrar_dashboard.php';</script>";
        exit();
    }

    echo "<script>alert('Failed to update section information. Please try again.'); window.history.back();</script>";
    exit();
}

if ($id === 0) {
    echo "<script>alert('No student selected.'); window.location.href='registrar_dashboard.php';</script>";
    exit();
}

$stmt = $conn->prepare("SELECT firstname, lastname, year, course, section, adviser FROM students_registration WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    echo "<script>alert('Student not found.'); window.location.href='registrar_dashboard.php';</script>";
    exit();
}

$currentSection = trim($student['section'] ?? '');
$currentAdviser = trim($student['adviser'] ?? '');

$baseSectionOptions = sectionSuggestions($conn, $student['year'] ?? '', $student['course'] ?? '');
$baseAdviserOptions = adviserSuggestions($conn, $student['year'] ?? '', $student['course'] ?? '');

$sectionOptions = $baseSectionOptions;
if ($currentSection !== '') {
    $sectionOptions = uniqueOptions(array_merge($sectionOptions, [$currentSection]));
}

$adviserOptions = $baseAdviserOptions;
if ($currentAdviser !== '') {
    $adviserOptions = uniqueOptions(array_merge($adviserOptions, [$currentAdviser]));
}

$sectionCustomSelected = $currentSection !== '' && !in_array($currentSection, $baseSectionOptions, true);
$adviserCustomSelected = $currentAdviser !== '' && !in_array($currentAdviser, $baseAdviserOptions, true) && $currentAdviser !== 'To be assigned';

$hasSectionSuggestions = !empty($baseSectionOptions);
$hasAdviserSuggestions = !empty($baseAdviserOptions);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Section</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f3;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 90%;
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.08);
        }
        h2 {
            color: #007f3f;
            border-bottom: 2px solid #007f3f;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }
        input[type="text"], select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .actions {
            margin-top: 25px;
            display: flex;
            gap: 10px;
        }
        button, a.button-link {
            background-color: #007f3f;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        button:hover, a.button-link:hover {
            background-color: #004d00;
        }
        .note {
            margin-top: 20px;
            font-size: 13px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Update Section</h2>
        <p><strong>Student:</strong> <?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']) ?></p>
        <p><strong>Grade Level:</strong> <?= htmlspecialchars($student['year']) ?></p>
        <?php if (!empty($student['course'])): ?>
            <p><strong>Strand / Course:</strong> <?= htmlspecialchars($student['course']) ?></p>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

            <?php if ($hasSectionSuggestions): ?>
                <label for="section_select">Section</label>
                <select id="section_select" name="section_select" required>
                    <option value="">Select section</option>
                    <?php foreach ($sectionOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option) ?>" <?= ($currentSection === $option) ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
                    <?php endforeach; ?>
                    <option value="__custom" <?= $sectionCustomSelected ? 'selected' : '' ?>>Custom section</option>
                </select>
                <input type="text" id="section_custom" name="section_custom" value="<?= htmlspecialchars($sectionCustomSelected ? $currentSection : '') ?>" placeholder="Enter custom section" <?= $sectionCustomSelected ? '' : 'style="display:none;"' ?> <?= $sectionCustomSelected ? 'required' : '' ?>>
            <?php else: ?>
                <label for="section_custom">Section</label>
                <input type="hidden" name="section_select" value="__custom">
                <input type="text" id="section_custom" name="section_custom" value="<?= htmlspecialchars($currentSection) ?>" placeholder="Enter section" required>
            <?php endif; ?>

            <?php if ($hasAdviserSuggestions): ?>
                <label for="adviser_select">Adviser</label>
                <select id="adviser_select" name="adviser_select">
                    <option value="">Select adviser</option>
                    <?php foreach ($adviserOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option) ?>" <?= ($currentAdviser === $option) ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
                    <?php endforeach; ?>
                    <option value="__custom" <?= $adviserCustomSelected ? 'selected' : '' ?>>Custom adviser</option>
                </select>
                <input type="text" id="adviser_custom" name="adviser_custom" value="<?= htmlspecialchars($adviserCustomSelected ? $currentAdviser : '') ?>" placeholder="Enter custom adviser" <?= $adviserCustomSelected ? '' : 'style="display:none;"' ?>>
            <?php else: ?>
                <label for="adviser_custom">Adviser</label>
                <input type="hidden" name="adviser_select" value="__custom">
                <input type="text" id="adviser_custom" name="adviser_custom" value="<?= htmlspecialchars($currentAdviser) ?>" placeholder="Assign adviser (optional)">
            <?php endif; ?>

            <div class="actions">
                <button type="submit">Save Changes</button>
                <a href="registrar_dashboard.php" class="button-link">Cancel</a>
            </div>
        </form>

        <p class="note">Choose one of the suggested sections or advisers, or pick “Custom” to type a different value.</p>
    </div>
    <script>
        (function() {
            const sectionSelect = document.getElementById('section_select');
            const sectionCustom = document.getElementById('section_custom');
            if (sectionSelect && sectionCustom) {
                const toggleSection = () => {
                    const useCustom = sectionSelect.value === '__custom';
                    sectionCustom.style.display = useCustom ? 'block' : 'none';
                    sectionCustom.required = useCustom;
                };
                toggleSection();
                sectionSelect.addEventListener('change', toggleSection);
            }

            const adviserSelect = document.getElementById('adviser_select');
            const adviserCustom = document.getElementById('adviser_custom');
            if (adviserSelect && adviserCustom) {
                const toggleAdviser = () => {
                    const useCustom = adviserSelect.value === '__custom';
                    adviserCustom.style.display = useCustom ? 'block' : 'none';
                    adviserCustom.required = false;
                };
                toggleAdviser();
                adviserSelect.addEventListener('change', toggleAdviser);
            }
        })();
    </script>
</body>
</html>
