#!/usr/bin/env python3
"""
Sentiment analysis server for analyzing feedback comments
"""
from http.server import HTTPServer, BaseHTTPRequestHandler
import json
import re
from nltk.sentiment.vader import SentimentIntensityAnalyzer
from urllib.parse import parse_qs, urlparse
import sys
import logging
import nltk
import os

# Set up logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

# Define language-specific sentiment words and patterns
SENTIMENT_WORDS = {
    'tagalog': {
        'positive': [
            # Basic positive words
            'maganda', 'masarap', 'mahusay', 'sulit', 'galing', 'astig', 
            'ang ganda', 'ang sarap', 'ang husay', 'napakaganda', 'napakasarap',
            'napakahusay', 'sobrang ganda', 'sobrang sarap', 'sobrang husay',
            'the best', 'super', 'excellent', 'amazing', 'wow',
            
            # Additional positive words
            'mabait', 'matulungin', 'mapagmahal', 'masaya', 'makabuluhan',
            'komportable', 'kasiya-siya', 'kapaki-pakinabang', 'kahanga-hanga',
            'marangal', 'magaling', 'magalang', 'masigasig', 'masigla',
            'malakas', 'maayos', 'matino', 'matapat', 'matibay',
            'makulay', 'mabango', 'malinis', 'malasa', 'malambot',
            'matagumpay', 'maaliwalas', 'madali', 'mabilis', 'maginhawa',
            
            # Positive phrases
            'napakasarap talaga', 'ang sarap-sarap', 'ang galing-galing',
            'talagang masarap', 'talagang maganda', 'sobrang sulit',
            'gustong-gusto ko', 'paborito ko', 'kahit kailan masarap',
            'hindi ako nabigo', 'nagustuhan ko', 'kakaiba ang sarap',
            'walang katulad', 'sulit na sulit', 'top quality', 'solid',
            'siguradong babalik', 'worth it', 'hindi pagsisisihan',
            'napakahusay na serbisyo', 'perpekto', 'perpektong timpla',
            'swak sa panlasa', 'bet na bet', 'panalo', 'aprubado',
            'legit', 'recommended', 'thumbs up', 'bet ko'
        ],
        'negative': [
            # Basic negative words
            'pangit', 'masama', 'hindi masarap', 'mahal', 'sayang', 
            'nakakainis', 'nakakadismaya', 'hindi maganda', 'hindi mahusay',
            'sobrang pangit', 'sobrang mahal', 'sobrang sama',
            'bad', 'poor', 'disappointing', 'not good', 'mapait',
            
            # Additional negative words
            'malansa', 'maalat', 'matamis masyado', 'matigas', 'makunat',
            'maasim', 'malamig', 'matabang', 'malabo', 'madumi',
            'madilim', 'masakit', 'mahina', 'mabagal', 'masaklap',
            'madugo', 'mabaho', 'mabigat', 'mahirap', 'madaya',
            'matagal', 'mapanghi', 'mapurol', 'mapait', 'makalat',
            'nakakatakot', 'nakakasuka', 'nakakaumay', 'nakakasawa',
            
            # Negative phrases
            'hindi ko type', 'hindi ko bet', 'hindi ko gusto',
            'sayang ang pera', 'sayang ang oras', 'hindi sulit',
            'hindi worth it', 'pagsisisihan mo', 'puro hangin',
            'puro drawing', 'walang kwenta', 'walang silbi',
            'sobrang disappointing', 'malaking kabiguan', 'overpriced',
            'overrated', 'kulang sa lasa', 'kulang sa timpla',
            'hindi fresh', 'hindi authentic', 'hindi totoo',
            'hindi maayos', 'hindi masarap talaga', 'matigas ang kanin',
            'malamig ang pagkain', 'mabagal ang serbisyo', 'walang lasa',
            'hindi ko recommendation', 'iwasan niyo', 'pumalpak',
            'thumbs down', 'skip this', 'pass', 'hard pass'
        ],
        'intensifiers': [
            'napaka', 'sobrang', 'ang', 'super', 'very', 'really',
            'grabe', 'ubod ng', 'tunay na', 'talagang', 'lubhang',
            'masyadong', 'labis na', 'sukdulan', 'todo', 'ultimate',
            'pinaka', 'the most', 'excessively', 'extremely',
            'lubos na', 'higit sa', 'higit pa sa', 'hindi maipaliwanag kung gaano'
        ]
    },
    'bisaya': {
        'positive': [
            # Basic positive words
            'nindot', 'lami', 'maayo', 'sulit', 'gwapo', 'nindot kaayo', 
            'lami kaayo', 'maayo kaayo', 'gwapo kaayo', 'lami jud',
            'nindot jud', 'maayo jud', 'gwapo jud',
            'the best', 'super', 'excellent', 'amazing', 'wow',
            
            # Additional positive words
            'hayahay', 'husto', 'habog', 'kusgan', 'buotan', 'matinabangon',
            'mahigugmaon', 'malipayon', 'mabulokon', 'maanindot',
            'hamis', 'hayag', 'hinlo', 'humot', 'hapsay', 'haum',
            'himsog', 'hinog', 'init-init', 'kalipay', 'kanindot',
            'kalami', 'kasadya', 'katahom', 'katam-is', 'kaayo',
            
            # Positive phrases
            'lami kaayo jud', 'nindot kaayo jud', 'dili ikasipag',
            'worth it jud', 'angay suwayon', 'angay adtoon',
            'sigurado mobalik', 'dili ka magmahay', 'paborito nako',
            'ganahan ko', 'lami gyud siya', 'wala pay kaparehas',
            'top notch', 'perfect kaayo', 'solid kaayo',
            'balik-balikon', 'nindot na kaayo', 'kampante ko',
            'kalami ba', 'kanindot ba', 'kaanindot', 'kalami jud',
            'kalipay sa panlasa', 'dili masayop', 'angay rekomendar'
        ],
        'negative': [
            # Basic negative words
            'dili nindot', 'dili lami', 'mahal', 'sayang', 'makalagot', 
            'makapasubo', 'dili maayo', 'dili gwapo',
            'dili kaayo nindot', 'dili kaayo lami', 'dili kaayo maayo',
            'bad', 'poor', 'disappointing', 'not good',
            
            # Additional negative words
            'baho', 'luya', 'pait', 'aslom', 'parat', 'tam-is kaayo',
            'gahi', 'bulingon', 'hugaw', 'ngil-ad', 'lain', 'grabe',
            'lawom', 'lalom', 'laay', 'luya', 'libog', 'labad',
            'lisod', 'makauulaw', 'makauulol', 'malahutayon',
            'mahal kaayo', 'makapabungol', 'makapalagot',
            
            # Negative phrases
            'dili angay suwayon', 'dili ko ganahan', 'dili worth it',
            'dili angay adtoon', 'sayang lang kwarta', 'sayang lang oras',
            'usik lang', 'wala juy lami', 'wala juy nindot',
            'pasagdi lang na', 'layo ra sa expectation', 'mahal ra kaayo',
            'kuwang sa lami', 'kuwang ug timpla', 'dili fresh',
            'dili tinuod', 'dili original', 'malangan', 'makuwangan',
            'dili hinog', 'dili masaligan', 'dili tarong',
            'dili ko mobalik', 'magbasol ka', 'magmahay ka',
            'dili ko morekom', 'ayaw pagpalit', 'ayaw pag-adto'
        ],
        'intensifiers': [
            'kaayo', 'jud', 'gyud', 'super', 'very', 'really',
            'grabe', 'karajaw', 'hilabihan', 'masukadon', 'pagkalain',
            'pagkanindot', 'pagkalami', 'pagkadaghan', 'pagkausik',
            'pagkadako', 'pagkadyutay', 'kaluoy', 'kataha',
            'kataw-anan', 'katingala', 'kahadlok', 'kasubo',
            'hilabihan ka', 'kusog kaayo', 'maayo kaayo'
        ]
    },
    'ilokano': {
        'positive': [
            # Basic positive words
            'nasayaat', 'naimas', 'napintas', 'nalaka', 'nagaget', 'nataraki',
            'nasayaat unay', 'naimas unay', 'napintas unay', 'nagsayaat',
            'nagpintas', 'nagimas', 'napintasen', 'naimasan', 'nasayaatan',
            
            # Additional positive words
            'narangrang', 'nalung-aw', 'naragsak', 'naragsakan', 'nanam-ay',
            'nakaay-ayo', 'natalged', 'natibker', 'natarnaw', 'natalinaay',
            'natakneng', 'natured', 'naulimek', 'naulpit', 'naannad',
            'nalinis', 'nadalus', 'nalaing', 'namit', 'nabanglo',
            'nabalikor', 'nabara', 'natadem', 'naingel', 'naragsak',
            
            # Positive phrases
            'nasayaat unay', 'naimas unay', 'napintas unay',
            'kasla paraiso', 'worth it', 'agsubli ak to', 'mayat a nagatendan',
            'rekomendado ko', 'paborito ko', 'awan pagkuranganna',
            'nasayaat ti serbisio', 'naanus dagiti agtagibalay',
            'naanus dagiti trabahador', 'naannayas ti kapadasan',
            'nagustoak unay', 'kas umuna a daras', 'napintas ti pagsasaadan',
            'naurnos', 'nadalus', 'agsubliak to manen'
        ],
        'negative': [
            # Basic negative words
            'saan a nasayaat', 'saan a naimas', 'saan a napintas', 'nangina',
            'nakaro', 'narigat', 'narugit', 'nalaad', 'nabangsit',
            'nakapsut', 'nakapoy', 'nakurapay', 'naunget', 'naliday',
            
            # Additional negative words
            'nabangles', 'nabara', 'nadagsen', 'nalidem', 'nalamiis',
            'napait', 'napudot', 'naladaw', 'nalaka a madadael',
            'nakurapay', 'nakurang', 'nakulbang', 'nalagda', 'nalaglag',
            'nalaka', 'naladingit', 'nalipit', 'nalukmeg', 'nalupoy',
            'nangina unay', 'narugit', 'naunget', 'naliday', 'napait',
            
            # Negative phrases
            'saan a worth it', 'saan nak to agsubli', 'saan a rekomendado',
            'sayang ti kuarta', 'sayang ti tiempo', 'awan serbina',
            'nagkurang ti raman', 'nagkurang ti rekado', 'nabayag a naidasar',
            'saan a fresh', 'saan nga assured ti kalidad', 'nauma',
            'awan ti special', 'awan napintas a maibaga', 'nakababain',
            'nakadidismaya', 'nakaupay', 'saan a naragsak', 'di napnek',
            'di nalaka a maawatan', 'di nalinis a lugar'
        ],
        'intensifiers': [
            'unay', 'adu', 'la unay', 'nagadu', 'kinalabes',
            'nakarkaro', 'sobra', 'sobra unay', 'extremely',
            'napalalo', 'nalabes', 'ketdi', 'pay', 'manen',
            'talaga', 'pudno nga', 'mismo', 'mann', 'uray la'
        ]
    },
    'hiligaynon': {
        'positive': [
            # Basic positive words
            'maayo', 'namit', 'manami', 'mayad', 'matahum', 'gwapa', 'gwapo',
            'manami gid', 'manamit gid', 'matahum gid', 'mayad gid',
            'maayo gid', 'manamit guid', 'pinalangga', 'halandon',
            
            # Additional positive words
            'masadya', 'malipayon', 'masinadyahon', 'maluluy-on', 'matinabangon',
            'mahigugmaon', 'mapinalanggaon', 'mapag-abiabi', 'mainunungon',
            'matinlo', 'matam-is', 'mainit-init', 'mainamon', 'malipayong',
            'maabtik', 'madasig', 'madalom', 'mahimulaton', 'maaghom',
            'maathag', 'maayo kaayo', 'masayon', 'madamo', 'mabaskug',
            
            # Positive phrases
            'namit gid kaayo', 'manami gid kaayo', 'mabalik-balikan',
            'sulit gid', 'angay sulayan', 'angay bisitahon', 'angay tikman',
            'manamit guid', 'worth it guid', 'mabalik ako', 'mabalik gid ako',
            'manami kag sulit', 'paborito ko', 'luyag ko guid',
            'wala guid kaparehas', 'perfect guid', 'hasta sunod',
            'recommendado ko', 'da best', 'kampante ko', 'palangga'
        ],
        'negative': [
            # Basic negative words
            'malain', 'indi maayo', 'indi namit', 'mahal', 'sayang', 'masakit',
            'mabudlay', 'malas-ay', 'mapait', 'makahuluya', 'mabudlay',
            'mabaho', 'masakit', 'madagmol', 'madakmol', 'madulom',
            
            # Additional negative words
            'malas-ay', 'mapait', 'maparat', 'maaslom', 'matam-is sobra',
            'matigas', 'mahugaw', 'mahigko', 'malain', 'grabe',
            'madalum', 'malaay', 'maluya', 'libog', 'labad',
            'mabudlay', 'makahuluy-a', 'makauulol', 'mabahol',
            'mahal guid', 'makapalagyo', 'makapaakig',
            
            # Negative phrases
            'indi angay sulayan', 'indi ko gusto', 'indi worth it',
            'indi angay bisitahon', 'sayang lang kwarta', 'sayang lang oras',
            'usik lang', 'wala gid namit', 'wala gid mayad',
            'pasagdi lang na', 'layo sa ginaexpect', 'mahal guid kaayo',
            'kulang sa namit', 'kulang sa rekado', 'indi bag-o',
            'indi tunay', 'indi original', 'mahinay', 'kulang',
            'indi hinog', 'indi masaligan', 'indi maayo',
            'indi ako mabalik', 'magabasol ka', 'magamahay ka',
            'indi ko recommendar', 'indi pagbaklon', 'indi pagkadtoan'
        ],
        'intensifiers': [
            'gid', 'guid', 'kaayo', 'super', 'very', 'really',
            'grabe', 'karajaw', 'tuman', 'sobra', 'talaga',
            'pagkamalain', 'pagkamayad', 'pagkanamit', 'pagkadamo', 'pagkausik',
            'pagkadako', 'pagkagamay', 'kaluoy', 'kataha',
            'katingala', 'kahadlok', 'kasubo', 'labaw pa', 'dugang pa'
        ]
    },
    'waray': {
        'positive': [
            # Basic positive words
            'maupay', 'malamit', 'matam-is', 'maopay', 'marahayon', 'harangdon',
            'maupay gud', 'malamit gud', 'maopay gud', 'marahayon gud',
            'maupay kaayo', 'malamit kaayo', 'maopay kaayo', 'bugana',
            
            # Additional positive words
            'masadya', 'malipayon', 'masinadyahon', 'maluluy-on', 'matinabangon',
            'mahigugmaon', 'mapinalanggaon', 'mapag-abi-abi', 'mainunungon',
            'mahusay', 'malinis', 'mahinlo', 'matinlo', 'mahamis',
            'maabtik', 'madali', 'madagmit', 'madasig', 'mahimulaton',
            'maathag', 'maupay kaayo', 'masayon', 'damo', 'makusog',
            
            # Positive phrases
            'malamit gud kaayo', 'maupay gud kaayo', 'babalik-balikan',
            'sulit gud', 'angay pagtalingohaon', 'angay bisitahon', 'angay tikman',
            'malamit guid', 'worth it guid', 'mabalik ako', 'mabalik gud ako',
            'malamit ngan sulit', 'paborito ko', 'gusto ko gud',
            'waray gud kapariho', 'perpekto gud', 'hasta sunod',
            'rekomendado ko', 'pinakamaupay', 'kompyansa ako', 'palangga'
        ],
        'negative': [
            # Basic negative words
            'maraot', 'diri maupay', 'diri malamit', 'mahal', 'sayang', 'masakit',
            'mabudlay', 'makaririkma', 'mapait', 'makahuluya', 'mabudlay',
            'mabaho', 'masakit', 'madagmol', 'madakmol', 'madulom',
            
            # Additional negative words
            'makaririgma', 'mapait', 'maparat', 'maaslom', 'matam-is sobra',
            'matigas', 'mahugaw', 'mahigko', 'maraot', 'grabe',
            'madalum', 'malaay', 'maluya', 'libog', 'labad',
            'mabudlay', 'makahuluy-a', 'makauulol', 'mabahol',
            'mahal gud', 'makapalagyo', 'makapaakig',
            
            # Negative phrases
            'diri angay pagtalingohaon', 'diri ko gusto', 'diri worth it',
            'diri angay bisitahon', 'sayang la an kwarta', 'sayang la an oras',
            'sayangan la', 'waray gud lamit', 'waray gud maupay',
            'pasagdi la ito', 'harayo ha gin-expect', 'mahal gud kaayo',
            'kulang ha lamit', 'kulang ha rekado', 'diri bag-o',
            'diri tinuod', 'diri orihinal', 'mahinay', 'kulang',
            'diri hinog', 'diri masaligan', 'diri maupay',
            'diri ako mabalik', 'mababasolan mo', 'mamamahayan mo',
            'diri ko irerekomendar', 'ayaw pagpalit', 'ayaw pagkadto'
        ],
        'intensifiers': [
            'gud', 'guid', 'kaayo', 'super', 'very', 'really',
            'grabe', 'karajaw', 'tuman', 'sobra', 'talaga',
            'pagkamaraot', 'pagkamaupay', 'pagkamalamit', 'pagkadamo', 'pagkasayang',
            'pagkadako', 'pagkaguti', 'kalooy', 'katahod',
            'katingala', 'kahadlok', 'kasubo', 'labaw pa', 'dugang pa'
        ]
    }
}

# Ensure NLTK data is available
def download_nltk_data():
    try:
        nltk.data.find('vader_lexicon')
    except LookupError:
        print("Downloading NLTK vader_lexicon...")
        nltk.download('vader_lexicon', download_dir=os.path.dirname(os.path.abspath(__file__)))
        nltk.data.path.append(os.path.dirname(os.path.abspath(__file__)))

# Download NLTK data at startup
download_nltk_data()

def detect_language(text):
    """
    Detect the primary language of the text based on word patterns
    """
    text = text.lower()
    # Count occurrences of language-specific words in the text
    language_counts = {}
    for lang in SENTIMENT_WORDS:
        positive_words = SENTIMENT_WORDS[lang]['positive']
        negative_words = SENTIMENT_WORDS[lang]['negative']
        language_counts[lang] = sum(1 for word in positive_words + negative_words if word in text)
    
    # Find the language with the most matching words
    if language_counts:
        max_lang = max(language_counts, key=language_counts.get)
        # Only return a specific language if it has at least one match
        if language_counts[max_lang] > 0:
            return max_lang
    
    # Default to English if no matches found
    return 'english'

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
            'score': 0,
            'original_text': text
        }
    
    try:
        # Clean the text
        clean_text = re.sub(r'[^\w\s]', '', text.lower())
        
        # Detect language
        language = detect_language(text)
        
        # Initialize sentiment score
        sentiment_score = 0
        word_count = 0
        
        # Check for language-specific sentiment words
        for lang in SENTIMENT_WORDS:
            # Check positive words
            for word in SENTIMENT_WORDS[lang]['positive']:
                if word in clean_text:
                    # Check for intensifiers
                    has_intensifier = any(intensifier in clean_text for intensifier in SENTIMENT_WORDS[lang]['intensifiers'])
                    sentiment_score += 1.5 if has_intensifier else 1
                    word_count += 1
            
            # Check negative words
            for word in SENTIMENT_WORDS[lang]['negative']:
                if word in clean_text:
                    # Check for intensifiers
                    has_intensifier = any(intensifier in clean_text for intensifier in SENTIMENT_WORDS[lang]['intensifiers'])
                    sentiment_score -= 1.5 if has_intensifier else 1
                    word_count += 1
        
        # If no language-specific words found, use VADER
        if word_count == 0:
            analyzer = SentimentIntensityAnalyzer()
            scores = analyzer.polarity_scores(clean_text)
            sentiment_score = scores['compound']
        else:
            # Normalize score
            sentiment_score = sentiment_score / word_count if word_count > 0 else 0
        
        # Categorize the sentiment
        if sentiment_score >= 0.1:
            category = 'positive'
        elif sentiment_score <= -0.1:
            category = 'negative'
        else:
            category = 'neutral'
            
        return {
            'category': category,
            'score': sentiment_score,
            'original_text': text,
            'language': language
        }
    except Exception as e:
        logging.error(f"Analysis error: {str(e)}")
        return {
            'category': 'neutral',
            'score': 0,
            'original_text': text,
            'error': str(e)
        }

class SentimentHandler(BaseHTTPRequestHandler):
    def _set_headers(self, status_code=200):
        self.send_response(status_code)
        self.send_header('Content-type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')
        self.end_headers()

    def do_OPTIONS(self):
        self._set_headers(204)

    def do_GET(self):
        try:
            # Parse URL and parameters
            parsed_url = urlparse(self.path)
            params = parse_qs(parsed_url.query)
            
            # Get text to analyze
            text = params.get('text', [''])[0]
            
            # Analyze sentiment
            result = analyze_text(text)
            
            # Send response
            self._set_headers()
            self.wfile.write(json.dumps(result).encode())
            
            # Log the analysis
            logging.info(f"Analyzed text: {text[:50]}... - Category: {result['category']}, Score: {result['score']}")
            
        except Exception as e:
            logging.error(f"Error processing request: {str(e)}")
            self._set_headers(500)
            self.wfile.write(json.dumps({
                'error': 'Internal server error',
                'message': str(e)
            }).encode())

    def do_POST(self):
        try:
            # Get content length
            content_length = int(self.headers.get('Content-Length', 0))
            post_data = self.rfile.read(content_length)
            
            # Parse JSON data
            data = json.loads(post_data.decode('utf-8'))
            
            # Check if this is a batch request
            if 'comments' in data:
                # Batch analysis
                results = []
                for comment in data['comments']:
                    result = analyze_text(comment)
                    # Return only the required fields
                    results.append({
                        'category': result['category'],
                        'score': result['score']
                    })
                
                # Send response
                self._set_headers()
                self.wfile.write(json.dumps(results).encode())
                
                # Log the analysis
                logging.info(f"Analyzed batch of {len(results)} comments")
            else:
                # Single comment analysis
                text = data.get('text', '')
                result = analyze_text(text)
                
                # Return only the required fields
                response = {
                    'category': result['category'],
                    'score': result['score']
                }
                
                # Send response
                self._set_headers()
                self.wfile.write(json.dumps(response).encode())
                
                # Log the analysis
                logging.info(f"Analyzed text: {text[:50]}... - Category: {result['category']}, Score: {result['score']}")
            
        except Exception as e:
            logging.error(f"Error processing request: {str(e)}")
            self._set_headers(500)
            self.wfile.write(json.dumps({
                'error': 'Internal server error',
                'message': str(e)
            }).encode())

def run_server(port=8000):
    server_address = ('', port)
    httpd = HTTPServer(server_address, SentimentHandler)
    logging.info(f'Starting sentiment analysis server on port {port}...')
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        logging.info('Shutting down server...')
        httpd.server_close()

if __name__ == "__main__":
    # Get port from command line argument or use default
    port = int(sys.argv[1]) if len(sys.argv) > 1 else 8000
    run_server(port)