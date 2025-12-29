<?php
if (!function_exists('getPageMetaDescription')) {
    function getPageMetaDescription($page, $params = []) {
        $defaultDescription = "BGC Science Club - Empowering students through science, technology, and innovation. Join our community of curious minds and explore the wonders of science.";
        
        $descriptions = [
            'home' => "Welcome to BGC Science Club - Fostering scientific curiosity and innovation among students. Join us for exciting projects, competitions, and learning opportunities.",
            'about' => "Learn about BGC Science Club's mission, vision, and the team behind our initiatives to promote science education and innovation.",
            'projects' => "Explore our innovative science projects and research initiatives at BGC Science Club. Discover how we're making a difference through science and technology.",
            'project' => isset($params['title']) 
                ? htmlspecialchars($params['title']) . ". " . (isset($params['short_description']) ? htmlspecialchars($params['short_description']) : (isset($params['excerpt']) ? htmlspecialchars($params['excerpt']) : 'Learn more about this exciting project from BGC Science Club.'))
                : (isset($params['slug']) ? "Discover this science project from BGC Science Club. Explore innovative ideas and research by our talented members." : "Explore innovative science projects and research initiatives at BGC Science Club."),
            'members' => "Meet the brilliant minds of BGC Science Club. Get to know our passionate members who are driving innovation and scientific exploration.",
            'member' => isset($params['name']) 
                ? "Learn more about " . htmlspecialchars($params['name']) . ", a dedicated member of BGC Science Club. " . (isset($params['role']) ? "Role: " . htmlspecialchars($params['role']) . "." : "")
                : "BGC Science Club member profile. Discover their contributions and achievements in the field of science.",
            'classes' => "Explore our educational programs and classes at BGC Science Club. We offer comprehensive learning experiences in various scientific disciplines.",
            'class' => isset($params['class_name']) 
                ? ($params['group'] ? ucfirst($params['group']) . ' - ' : '') . htmlspecialchars($params['class_name']) . " - BGC Science Club. " . ($params['description'] ?? "Join our specialized science program for comprehensive learning and hands-on experience.")
                : "BGC Science Club class information. Join our specialized programs to enhance your scientific knowledge and skills.",
            'activities' => "Discover the exciting activities, workshops, and events organized by BGC Science Club. Join us for hands-on learning experiences and scientific exploration.",
            'activity' => isset($params['title']) 
                ? htmlspecialchars($params['title']) . ". " . (isset($params['short_description']) ? htmlspecialchars($params['short_description']) : (isset($params['description']) ? (strlen($params['description']) > 150 ? substr(htmlspecialchars($params['description']), 0, 147) . '...' : htmlspecialchars($params['description'])) : "Join us for this exciting activity at BGC Science Club."))
                : (isset($params['slug']) ? "Discover this activity from BGC Science Club. Join us for exciting learning opportunities." : "Discover exciting activities, workshops, and events at BGC Science Club."),
            'events' => "Stay updated with upcoming science events, workshops, and competitions organized by BGC Science Club. Join us for exciting learning opportunities.",
            'event' => isset($params['title']) 
                ? htmlspecialchars($params['title']) . ". " . (isset($params['date']) ? date('F j, Y', strtotime($params['date'])) . ". " : "") . (isset($params['description']) ? htmlspecialchars($params['description']) : "Join us for this exciting event at BGC Science Club.")
                : "Upcoming event at BGC Science Club. Join us for an exciting opportunity to learn and explore science.",
            'executives' => "Meet the dedicated team of executives and coordinators leading BGC Science Club. Learn about our leadership and their commitment to scientific excellence.",
            'executive' => isset($params['name']) 
                ? htmlspecialchars($params['name']) . " - " . (isset($params['role']) ? htmlspecialchars($params['role']) : 'Executive') . " at BGC Science Club. " . (isset($params['bio']) && strlen($params['bio']) > 0 ? (strlen($params['bio']) > 100 ? substr(htmlspecialchars($params['bio']), 0, 97) . '...' : htmlspecialchars($params['bio'])) : "Learn more about our executive team member.")
                : (isset($params['slug']) ? "Meet a member of the BGC Science Club executive team." : "Meet the executives leading BGC Science Club."),
            'departments' => "Explore the diverse science departments at BGC Science Club. From ICT to Physics, Chemistry, Biology, and Mathematics - discover our academic divisions.",
            'department' => isset($params['department_name']) 
                ? "Department of " . htmlspecialchars($params['department_name']) . " at BGC Science Club. Explore members, projects, and activities from this department."
                : "Explore departments at BGC Science Club. Discover our academic divisions and their contributions to science education.",
            'classes' => "Explore our educational programs and classes at BGC Science Club. We offer comprehensive learning experiences in various scientific disciplines.",
            'blog' => "Read our latest articles, research papers, and science insights from BGC Science Club members and experts in the field.",
            'article' => isset($params['title']) 
                ? htmlspecialchars($params['title']) . ". " . (isset($params['excerpt']) ? htmlspecialchars($params['excerpt']) : "Read this article from BGC Science Club.")
                : "Read this article from BGC Science Club. Explore the latest in science and technology.",
            'contact' => "Get in touch with BGC Science Club. We'd love to hear from you about collaborations, questions, or joining our community.",
            'join' => "Join BGC Science Club today! Become part of a vibrant community of science enthusiasts and innovators. Apply now to start your journey.",
            'gallery' => "Browse our photo gallery showcasing events, projects, and activities at BGC Science Club. See our community in action!",
            'achievements' => "Celebrating the achievements and awards of BGC Science Club members. Discover our proud moments and success stories.",
            'resources' => "Access valuable science resources, study materials, and educational content curated by BGC Science Club for students and educators.",
        ];

        // Special handling for class pages with groups (Science/Commerce/Arts)
        if ($page === 'class' && !empty($params['group'])) {
            $group = ucfirst(strtolower($params['group']));
            $class = isset($params['class_name']) ? $params['class_name'] : '';
            
            $groupDescriptions = [
                'science' => "Science stream ${class} at BGC Science Club. Comprehensive science education with hands-on experiments, research opportunities, and expert guidance.",
                'commerce' => "Commerce stream ${class} at BGC Science Club. Developing business acumen and analytical skills for future leaders and entrepreneurs.",
                'arts' => "Arts stream ${class} at BGC Science Club. Fostering creativity and critical thinking through humanities and social sciences.",
            ];
            
            if (isset($groupDescriptions[strtolower($params['group'])])) {
                return $groupDescriptions[strtolower($params['group'])];
            }
        }
        
        // Handle filtered pages
        if ($page === 'projects' && (!empty($params['class_filter']) || !empty($params['department_filter']) || !empty($params['member_id_filter']) || !empty($params['search_filter']) || !empty($params['collaborative_filter']))) {
            $filterParts = [];
            if (!empty($params['class_filter'])) {
                $filterParts[] = htmlspecialchars($params['class_filter']);
            }
            if (!empty($params['department_filter'])) {
                $filterParts[] = "Department of " . htmlspecialchars($params['department_filter']);
            }
            if (!empty($params['member_id_filter'])) {
                $filterParts[] = "member " . htmlspecialchars($params['member_id_filter']);
            }
            if (!empty($params['collaborative_filter'])) {
                $filterParts[] = "club-wide projects";
            }
            if (!empty($params['search_filter'])) {
                return "Search results for '" . htmlspecialchars($params['search_filter']) . "' in BGC Science Club projects. Find innovative science projects and research initiatives.";
            }
            if (!empty($filterParts)) {
                return "Explore science projects " . implode(" and ", $filterParts) . " at BGC Science Club. Discover innovative research and student contributions.";
            }
        }
        
        if ($page === 'members' && (!empty($params['department_filter']) || !empty($params['class_level_filter']) || !empty($params['search_filter']))) {
            $filterParts = [];
            if (!empty($params['department_filter'])) {
                $filterParts[] = "from Department of " . htmlspecialchars($params['department_filter']);
            }
            if (!empty($params['class_level_filter'])) {
                $filterParts[] = "from " . htmlspecialchars($params['class_level_filter']);
            }
            if (!empty($params['search_filter'])) {
                return "Search results for '" . htmlspecialchars($params['search_filter']) . "' in BGC Science Club members. Meet our passionate community members.";
            }
            if (!empty($filterParts)) {
                return "Meet BGC Science Club members " . implode(" and ", $filterParts) . ". Get to know our dedicated community of science enthusiasts.";
            }
        }

        return $descriptions[$page] ?? $defaultDescription;
    }
}

// Alias for backward compatibility
if (!function_exists('getMetaDescription')) {
    function getMetaDescription($page, $params = []) {
        return getPageMetaDescription($page, $params);
    }
}
?>
