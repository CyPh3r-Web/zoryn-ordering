#!/usr/bin/env python3
"""
Sentiment analysis script for analyzing feedback comments
"""
import sys
import json
import argparse
import re
from nltk.sentiment.vader import SentimentIntensityAnalyzer

# Ensure NLTK data is available
try:
    import nltk
    nltk.data.find('vader_lexicon')
except LookupError:
    nltk.download('vader_lexicon')

def analyze_text(text):
    """
    Analyze the sentiment of a given text
    
    Args:
        text (str): Text to analyze
            
    Returns:
        dict: Sentiment analysis result with category and score
    """
    if not text or text == 'N/A' or len(text.strip()) == 0:
        return {
            'category': 'neutral',
            'score': 0
        }
            
    # Clean the text
    text = re.sub(r'[^\w\s]', '', text.lower())
    
    # Get sentiment scores
    analyzer = SentimentIntensityAnalyzer()
    scores = analyzer.polarity_scores(text)
    
    # Categorize the sentiment
    if scores['compound'] >= 0.05:
        category = 'positive'
    elif scores['compound'] <= -0.05:
        category = 'negative'
    else:
        category = 'neutral'
        
    return {
        'category': category,
        'score': scores['compound']
    }

def main():
    """Parse arguments and run sentiment analysis"""
    parser = argparse.ArgumentParser(description='Analyze text sentiment')
    parser.add_argument('--analyze-text', help='Text to analyze')
    
    args = parser.parse_args()
    
    if args.analyze_text:
        result = analyze_text(args.analyze_text)
        print(json.dumps(result))
    else:
        print(json.dumps({'category': 'neutral', 'score': 0}))

if __name__ == "__main__":
    main()