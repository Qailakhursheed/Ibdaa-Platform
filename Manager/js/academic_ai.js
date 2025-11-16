/**
 * ===============================================
 * 🤖 ACADEMIC AI/ML INTEGRATION
 * ===============================================
 * نظام ذكاء اصطناعي متقدم للأنظمة الأكاديمية
 * ===============================================
 * Features:
 * - Auto-Grading للواجبات
 * - Plagiarism Detection
 * - Question Generation
 * - Predictive Analytics
 * - Performance Insights
 * - Smart Recommendations
 * ===============================================
 */

class AcademicAI {
    constructor() {
        this.mlModel = null;
        this.sentimentAnalyzer = null;
        this.plagiarismDetector = null;
        
        this.init();
    }

    async init() {
        console.log('🤖 Initializing Academic AI System...');
        
        // Initialize TensorFlow.js model for predictions
        if (typeof tf !== 'undefined') {
            await this.loadPredictionModel();
        }
        
        // Initialize NLP tools
        if (typeof compromise !== 'undefined') {
            this.nlp = compromise;
        }
        
        console.log('✅ Academic AI System Ready');
    }

    /**
     * ============================================
     * 1. AUTO-GRADING FOR ASSIGNMENTS
     * ============================================
     */
    async autoGradeAssignment(submissionText, rubric) {
        console.log('🤖 Auto-grading assignment...');
        
        const results = {
            scores: {},
            totalScore: 0,
            maxScore: 0,
            feedback: [],
            confidence: 0
        };

        if (!rubric || !rubric.criteria) {
            return this.simpleAutoGrade(submissionText);
        }

        // Grade based on rubric criteria
        for (const criterion of rubric.criteria) {
            const score = await this.gradeCriterion(submissionText, criterion);
            results.scores[criterion.name] = score;
            results.totalScore += score.points;
            results.maxScore += criterion.maxPoints;
            results.feedback.push(score.feedback);
        }

        results.percentage = (results.totalScore / results.maxScore) * 100;
        results.confidence = this.calculateConfidence(results);

        return results;
    }

    async gradeCriterion(text, criterion) {
        const result = {
            points: 0,
            maxPoints: criterion.maxPoints,
            feedback: ''
        };

        // Word count check
        if (criterion.minWords) {
            const wordCount = text.split(/\s+/).length;
            if (wordCount < criterion.minWords) {
                result.feedback = `عدد الكلمات قليل جداً (${wordCount}/${criterion.minWords})`;
                result.points = criterion.maxPoints * 0.5;
                return result;
            }
        }

        // Keyword matching
        if (criterion.keywords && criterion.keywords.length > 0) {
            const foundKeywords = criterion.keywords.filter(keyword => 
                text.toLowerCase().includes(keyword.toLowerCase())
            );
            const keywordScore = (foundKeywords.length / criterion.keywords.length);
            result.points = criterion.maxPoints * keywordScore;
            result.feedback = `تم العثور على ${foundKeywords.length} من ${criterion.keywords.length} كلمات مفتاحية`;
        } else {
            // General quality check
            const quality = this.assessTextQuality(text);
            result.points = criterion.maxPoints * quality.score;
            result.feedback = quality.feedback;
        }

        return result;
    }

    simpleAutoGrade(text) {
        const quality = this.assessTextQuality(text);
        return {
            totalScore: quality.score * 100,
            maxScore: 100,
            percentage: quality.score * 100,
            feedback: [quality.feedback],
            confidence: 0.65
        };
    }

    assessTextQuality(text) {
        let score = 0.5; // Base score
        const feedback = [];

        // Length check
        const wordCount = text.split(/\s+/).length;
        if (wordCount > 100) score += 0.1;
        if (wordCount > 300) score += 0.1;
        if (wordCount > 500) score += 0.1;

        // Sentence structure
        const sentences = text.split(/[.!?]+/).filter(s => s.trim().length > 0);
        if (sentences.length > 3) score += 0.05;
        if (sentences.length > 10) score += 0.05;

        // Paragraph structure
        const paragraphs = text.split(/\n\n+/).filter(p => p.trim().length > 0);
        if (paragraphs.length > 1) score += 0.1;

        score = Math.min(1.0, score);

        return {
            score: score,
            feedback: `جودة النص: ${Math.round(score * 100)}% (${wordCount} كلمة، ${sentences.length} جملة، ${paragraphs.length} فقرة)`
        };
    }

    calculateConfidence(results) {
        // Higher confidence if more criteria matched
        const criteriaCount = Object.keys(results.scores).length;
        return Math.min(0.95, 0.6 + (criteriaCount * 0.05));
    }

    /**
     * ============================================
     * 2. PLAGIARISM DETECTION
     * ============================================
     */
    async detectPlagiarism(text, compareTexts = []) {
        console.log('🔍 Detecting plagiarism...');

        const results = {
            score: 0,
            matches: [],
            verdict: 'original',
            confidence: 0.8
        };

        // Fingerprint the text
        const fingerprint = this.createTextFingerprint(text);

        // Compare with previous submissions
        for (const compareText of compareTexts) {
            const compareFingerprint = this.createTextFingerprint(compareText.text);
            const similarity = this.calculateSimilarity(fingerprint, compareFingerprint);

            if (similarity > 0.3) {
                results.matches.push({
                    source: compareText.source || 'مصدر غير معروف',
                    similarity: Math.round(similarity * 100),
                    matchedPhrases: this.findMatchingPhrases(text, compareText.text)
                });
            }
        }

        // Calculate overall score
        if (results.matches.length > 0) {
            results.score = Math.max(...results.matches.map(m => m.similarity));
            
            if (results.score > 70) {
                results.verdict = 'high_plagiarism';
            } else if (results.score > 40) {
                results.verdict = 'moderate_plagiarism';
            } else {
                results.verdict = 'low_plagiarism';
            }
        }

        return results;
    }

    createTextFingerprint(text) {
        const cleaned = text.toLowerCase()
            .replace(/[^\w\s\u0600-\u06FF]/g, '')
            .replace(/\s+/g, ' ')
            .trim();

        // N-grams (3-word phrases)
        const words = cleaned.split(' ');
        const ngrams = new Set();

        for (let i = 0; i < words.length - 2; i++) {
            ngrams.add(`${words[i]} ${words[i+1]} ${words[i+2]}`);
        }

        return ngrams;
    }

    calculateSimilarity(fingerprint1, fingerprint2) {
        const intersection = new Set([...fingerprint1].filter(x => fingerprint2.has(x)));
        const union = new Set([...fingerprint1, ...fingerprint2]);
        
        return intersection.size / union.size;
    }

    findMatchingPhrases(text1, text2) {
        const words1 = text1.toLowerCase().split(/\s+/);
        const words2 = text2.toLowerCase().split(/\s+/);
        const matches = [];

        for (let i = 0; i < words1.length - 4; i++) {
            const phrase = words1.slice(i, i + 5).join(' ');
            if (text2.toLowerCase().includes(phrase)) {
                matches.push(phrase);
            }
        }

        return matches.slice(0, 5); // Top 5 matches
    }

    /**
     * ============================================
     * 3. QUESTION GENERATION
     * ============================================
     */
    async generateQuestions(topic, difficulty = 'medium', count = 5, questionType = 'multiple_choice') {
        console.log(`🎯 Generating ${count} ${difficulty} ${questionType} questions about: ${topic}`);

        const questions = [];

        for (let i = 0; i < count; i++) {
            let question;
            
            switch (questionType) {
                case 'multiple_choice':
                    question = this.generateMCQ(topic, difficulty);
                    break;
                case 'true_false':
                    question = this.generateTrueFalse(topic, difficulty);
                    break;
                case 'short_answer':
                    question = this.generateShortAnswer(topic, difficulty);
                    break;
                default:
                    question = this.generateMCQ(topic, difficulty);
            }

            questions.push(question);
        }

        return questions;
    }

    generateMCQ(topic, difficulty) {
        const templates = [
            `ما هو/ما هي ${topic}؟`,
            `كيف يمكن تعريف ${topic}؟`,
            `أي من التالي يصف ${topic} بشكل صحيح؟`,
            `ما هي الخاصية الرئيسية لـ ${topic}؟`,
            `في أي سياق يُستخدم ${topic}؟`
        ];

        const template = templates[Math.floor(Math.random() * templates.length)];

        return {
            question_type: 'multiple_choice',
            question_text: template,
            options: this.generateOptions(topic, difficulty),
            points: difficulty === 'hard' ? 3 : difficulty === 'medium' ? 2 : 1,
            difficulty: difficulty,
            explanation: `هذا السؤال يختبر فهمك لـ ${topic}`
        };
    }

    generateOptions(topic, difficulty) {
        // This is a simplified version - in production, use GPT or similar
        return {
            'A': `خيار متعلق بـ ${topic} (1)`,
            'B': `خيار متعلق بـ ${topic} (2)`,
            'C': `خيار متعلق بـ ${topic} (3)`,
            'D': `خيار متعلق بـ ${topic} (4)`
        };
    }

    generateTrueFalse(topic, difficulty) {
        const statements = [
            `${topic} هو مفهوم أساسي في المجال`,
            `يمكن استخدام ${topic} في جميع الحالات`,
            `${topic} لا يحتاج إلى فهم عميق`,
            `${topic} يعتبر من المواضيع المتقدمة`
        ];

        return {
            question_type: 'true_false',
            question_text: statements[Math.floor(Math.random() * statements.length)],
            points: 1,
            difficulty: difficulty
        };
    }

    generateShortAnswer(topic, difficulty) {
        const templates = [
            `اشرح مفهوم ${topic} بإيجاز`,
            `ما هي أهمية ${topic}؟`,
            `أعط مثالاً على استخدام ${topic}`,
            `قارن بين ${topic} ومفاهيم أخرى`
        ];

        return {
            question_type: 'short_answer',
            question_text: templates[Math.floor(Math.random() * templates.length)],
            points: difficulty === 'hard' ? 5 : difficulty === 'medium' ? 3 : 2,
            difficulty: difficulty,
            min_words: difficulty === 'hard' ? 100 : difficulty === 'medium' ? 50 : 25
        };
    }

    /**
     * ============================================
     * 4. PREDICTIVE ANALYTICS
     * ============================================
     */
    async predictFinalGrade(studentData) {
        console.log('📊 Predicting final grade...');

        // Extract features
        const features = {
            currentAverage: studentData.currentAverage || 0,
            assignmentsCompleted: studentData.assignmentsCompleted || 0,
            assignmentsTotal: studentData.assignmentsTotal || 1,
            attendance: studentData.attendanceRate || 0,
            participation: studentData.participationScore || 0,
            timeInvestment: studentData.avgTimePerAssignment || 0
        };

        // Simple prediction model (in production, use trained ML model)
        let prediction = features.currentAverage;

        // Adjust based on completion rate
        const completionRate = features.assignmentsCompleted / features.assignmentsTotal;
        if (completionRate < 0.5) {
            prediction *= 0.8;
        } else if (completionRate > 0.9) {
            prediction *= 1.05;
        }

        // Adjust based on attendance
        if (features.attendance < 0.7) {
            prediction *= 0.9;
        } else if (features.attendance > 0.9) {
            prediction *= 1.03;
        }

        // Adjust based on participation
        prediction = prediction * 0.85 + features.participation * 0.15;

        prediction = Math.max(0, Math.min(100, prediction));

        return {
            predictedGrade: Math.round(prediction * 10) / 10,
            confidence: this.calculatePredictionConfidence(features),
            factors: this.analyzeFactors(features),
            recommendations: this.generateRecommendations(features, prediction)
        };
    }

    calculatePredictionConfidence(features) {
        const completionRate = features.assignmentsCompleted / features.assignmentsTotal;
        
        // Higher confidence with more data
        let confidence = 0.5 + (completionRate * 0.4);
        
        // Increase confidence if consistent performance
        if (features.currentAverage > 0) {
            confidence += 0.1;
        }
        
        return Math.min(0.95, confidence);
    }

    analyzeFactors(features) {
        const factors = [];

        const completionRate = features.assignmentsCompleted / features.assignmentsTotal;
        
        if (completionRate < 0.7) {
            factors.push({
                factor: 'إكمال الواجبات',
                impact: 'negative',
                strength: 'high',
                message: 'معدل إكمال الواجبات منخفض'
            });
        }

        if (features.attendance < 0.8) {
            factors.push({
                factor: 'الحضور',
                impact: 'negative',
                strength: 'medium',
                message: 'معدل الحضور يحتاج لتحسين'
            });
        }

        if (features.currentAverage > 85) {
            factors.push({
                factor: 'الأداء الحالي',
                impact: 'positive',
                strength: 'high',
                message: 'أداء ممتاز حتى الآن'
            });
        }

        return factors;
    }

    generateRecommendations(features, predictedGrade) {
        const recommendations = [];

        const completionRate = features.assignmentsCompleted / features.assignmentsTotal;

        if (completionRate < 0.8) {
            recommendations.push({
                priority: 'high',
                category: 'واجبات',
                action: 'إكمال الواجبات المتبقية',
                impact: '+5-10 درجات'
            });
        }

        if (features.attendance < 0.85) {
            recommendations.push({
                priority: 'medium',
                category: 'حضور',
                action: 'تحسين معدل الحضور',
                impact: '+3-5 درجات'
            });
        }

        if (predictedGrade < 70) {
            recommendations.push({
                priority: 'high',
                category: 'دراسة',
                action: 'تخصيص وقت إضافي للمراجعة',
                impact: '+10-15 درجات'
            });
        }

        if (features.participation < 60) {
            recommendations.push({
                priority: 'low',
                category: 'مشاركة',
                action: 'زيادة المشاركة في الفصل',
                impact: '+2-3 درجات'
            });
        }

        return recommendations;
    }

    /**
     * ============================================
     * 5. PERFORMANCE INSIGHTS
     * ============================================
     */
    async generatePerformanceInsights(studentData, courseData) {
        console.log('💡 Generating performance insights...');

        const insights = {
            strengths: [],
            weaknesses: [],
            trends: [],
            comparisons: [],
            recommendations: []
        };

        // Analyze strengths
        if (studentData.assignmentAverage > 85) {
            insights.strengths.push('أداء ممتاز في الواجبات');
        }
        if (studentData.examAverage > 80) {
            insights.strengths.push('أداء قوي في الامتحانات');
        }
        if (studentData.attendanceRate > 0.9) {
            insights.strengths.push('التزام عالي بالحضور');
        }

        // Analyze weaknesses
        if (studentData.assignmentAverage < 60) {
            insights.weaknesses.push('يحتاج لتحسين في الواجبات');
        }
        if (studentData.examAverage < 60) {
            insights.weaknesses.push('أداء ضعيف في الامتحانات');
        }
        if (studentData.participationScore < 50) {
            insights.weaknesses.push('مشاركة محدودة في الفصل');
        }

        // Analyze trends
        if (studentData.recentGrades && studentData.recentGrades.length > 2) {
            const trend = this.analyzeTrend(studentData.recentGrades);
            insights.trends.push(trend);
        }

        // Compare with class average
        if (courseData && courseData.classAverage) {
            const diff = studentData.currentAverage - courseData.classAverage;
            insights.comparisons.push({
                metric: 'المعدل العام',
                studentValue: studentData.currentAverage,
                classValue: courseData.classAverage,
                difference: diff,
                status: diff > 0 ? 'أعلى من المعدل' : 'أقل من المعدل'
            });
        }

        // Generate recommendations based on analysis
        insights.recommendations = this.generateDetailedRecommendations(insights);

        return insights;
    }

    analyzeTrend(grades) {
        if (grades.length < 2) return { direction: 'stable', message: 'غير كافٍ للتحليل' };

        const recent = grades.slice(-3);
        const older = grades.slice(0, -3);

        const recentAvg = recent.reduce((a, b) => a + b, 0) / recent.length;
        const olderAvg = older.length > 0 ? older.reduce((a, b) => a + b, 0) / older.length : recentAvg;

        const diff = recentAvg - olderAvg;

        if (diff > 5) {
            return { direction: 'improving', message: 'الأداء يتحسن بشكل ملحوظ', trend: '+' };
        } else if (diff < -5) {
            return { direction: 'declining', message: 'الأداء يتراجع - يحتاج لانتباه', trend: '-' };
        } else {
            return { direction: 'stable', message: 'الأداء مستقر', trend: '→' };
        }
    }

    generateDetailedRecommendations(insights) {
        const recommendations = [];

        // Based on weaknesses
        if (insights.weaknesses.some(w => w.includes('واجبات'))) {
            recommendations.push({
                priority: 1,
                title: 'تحسين الواجبات',
                actions: [
                    'البدء مبكراً في حل الواجبات',
                    'طلب المساعدة من المدرب عند الحاجة',
                    'مراجعة المواد قبل البدء بالحل'
                ]
            });
        }

        if (insights.weaknesses.some(w => w.includes('امتحانات'))) {
            recommendations.push({
                priority: 1,
                title: 'تحسين الاستعداد للامتحانات',
                actions: [
                    'إنشاء جدول مراجعة منظم',
                    'حل امتحانات سابقة',
                    'المراجعة الجماعية مع الزملاء'
                ]
            });
        }

        // Based on trends
        if (insights.trends.some(t => t.direction === 'declining')) {
            recommendations.push({
                priority: 1,
                title: 'عكس الاتجاه السلبي',
                actions: [
                    'تحديد أسباب التراجع',
                    'وضع أهداف قصيرة المدى',
                    'طلب دعم إضافي'
                ]
            });
        }

        return recommendations;
    }

    /**
     * ============================================
     * 6. ML MODEL LOADING (TensorFlow.js)
     * ============================================
     */
    async loadPredictionModel() {
        try {
            // In production, load a pre-trained model
            // this.mlModel = await tf.loadLayersModel('/models/grade-predictor/model.json');
            console.log('📦 ML model would be loaded here');
        } catch (error) {
            console.error('Failed to load ML model:', error);
        }
    }

    async predictWithTensorFlow(features) {
        if (!this.mlModel) {
            console.warn('ML model not loaded');
            return null;
        }

        try {
            const tensor = tf.tensor2d([features]);
            const prediction = this.mlModel.predict(tensor);
            const result = await prediction.data();
            
            tensor.dispose();
            prediction.dispose();
            
            return result[0];
        } catch (error) {
            console.error('Prediction error:', error);
            return null;
        }
    }
}

// ============================================
// EXPORT
// ============================================
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AcademicAI;
}

// Global instance
if (typeof window !== 'undefined') {
    window.AcademicAI = new AcademicAI();
}

console.log('✅ Academic AI Module Loaded');
