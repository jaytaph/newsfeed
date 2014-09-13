<?php

namespace Noxlogic\FeedBundle\Controller;

use Exception;
use NlpTools\Documents\TokensDocument;
use NlpTools\Tokenizers\PennTreeBankTokenizer;
use NlpTools\Utils\Normalizers\Normalizer;
use NlpTools\Utils\StopWords;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\ProcessBuilder;

class FeedController extends Controller
{

    protected $stopwords = array(
        "a", "about", "above", "above", "across", "after", "afterwards", "again", "against", "all", "almost", "alone",
        "along", "already", "also","although","always", "am","among", "amongst", "amoungst", "amount",  "an", "and",
        "another", "any","anyhow","anyone","anything","anyway", "anywhere", "are", "around", "as",  "at", "back","be",
        "became", "because","become","becomes", "becoming", "been", "before", "beforehand", "behind", "being", "below",
        "beside", "besides", "between", "beyond", "bill", "both", "bottom","but", "by", "call", "can", "cannot", "cant",
        "co", "con", "could", "couldnt", "cry", "de", "describe", "detail", "do", "done", "down", "due", "during",
        "each", "eg", "eight", "either", "eleven","else", "elsewhere", "empty", "enough", "etc", "even", "ever",
        "every", "everyone", "everything", "everywhere", "except", "few", "fifteen", "fify", "fill", "find", "fire",
        "first", "five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further",
        "get", "give", "go", "had", "has", "hasnt", "have", "he", "hence", "her", "here", "hereafter", "hereby",
        "herein", "hereupon", "hers", "herself", "him", "himself", "his", "how", "however", "hundred", "ie", "if", "in",
        "inc", "indeed", "interest", "into", "is", "it", "its", "itself", "keep", "last", "latter", "latterly", "least",
        "less", "ltd", "made", "many", "may", "me", "meanwhile", "might", "mill", "mine", "more", "moreover", "most",
        "mostly", "move", "much", "must", "my", "myself", "name", "namely", "neither", "never", "nevertheless", "next",
        "nine", "no", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "on",
        "once", "one", "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out", "over",
        "own","part", "per", "perhaps", "please", "put", "rather", "re", "same", "see", "seem", "seemed", "seeming",
        "seems", "serious", "several", "she", "should", "show", "side", "since", "sincere", "six", "sixty", "so",
        "some", "somehow", "someone", "something", "sometime", "sometimes", "somewhere", "still", "such", "system",
        "take", "ten", "than", "that", "the", "their", "them", "themselves", "then", "thence", "there", "thereafter",
        "thereby", "therefore", "therein", "thereupon", "these", "they", "thickv", "thin", "third", "this", "those",
        "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "top", "toward", "towards",
        "twelve", "twenty", "two", "un", "under", "until", "up", "upon", "us", "very", "via", "was", "we", "well",
        "were", "what", "whatever", "when", "whence", "whenever", "where", "whereafter", "whereas", "whereby",
        "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "whoever", "whole", "whom",
        "whose", "why", "will", "with", "within", "without", "would", "yet", "you", "your", "yours", "yourself",
        "yourselves", "the");

    protected $urls = array(
        'phpdeveloper' => 'http://www.phpdeveloper.org/feed',
        'Sensiolabs UK' => 'http://www.sensiolabs.co.uk/blog/feed.xml',
    );

    public function listAction()
    {
        $feeds = array();
        foreach ($this->urls as $title => $url) {
            $reader = $this->container->get('debril.reader');
            try {
                $feed = $reader->getFeedContent($url);
                $feeds[$title] = $feed;
            } catch (\Exception $e) {
            }
        }

        return $this->render('NoxlogicFeedBundle:Feed:list.html.twig', array('feeds' => $feeds));
    }

    public function tokenizeAction($url) {
        $text = $this->fetchText($url);

        //$tok = new WhitespaceAndPunctuationTokenizer();
        $tok = new PennTreeBankTokenizer();
        $tokens = $tok->tokenize($text);

        $stop = new StopWords($this->stopwords);

        $doc = new TokensDocument($tokens);
        $doc->applyTransformation(Normalizer::factory("English"));
        $doc->applyTransformation($stop);
        $tokens = $doc->getDocumentData();


        // More filtering
        foreach ($tokens as $k => $v) {
            $tokens[$k] = strtolower($tokens[$k]);

            $tokens[$k] = trim($tokens[$k], '.');

            //if (in_array($v, $this->stopwords)) unset($tokens[$k]);
            if (strlen($v) <= 3) unset($tokens[$k]);
        }

        // Count the rest
        $count = array_count_values($tokens);
        arsort($count);

        print "<pre>";
        print_r($count);
        print "</pre>";

        print "<hr>";
        print_r($tokens);
        return new Response();
    }


    protected function fetchText($url)
    {
        $rootDir = $this->container->get('kernel')->getRootdir();

        $builder = new ProcessBuilder(array(
            '/usr/bin/java',
            '-jar',
            $rootDir.'/../binaries/tika-app-1.6.jar',
            '--text-main',
            urldecode($url),
        ));
        $process = $builder->getProcess();
        $process->run();

        $text = $process->getOutput();
        return $text;
    }
}

