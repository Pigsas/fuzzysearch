<?php

namespace Pigsas\FuzzySearch\Service;

use Configuration;
use Db;
use PDO;
use Shop;
use TeamTNT\TNTSearch\Support\Collection;
use TeamTNT\TNTSearch\TNTSearch;
use Throwable;

class SearchService extends TNTSearch {

    public $wordList = [];

    public function __construct()
    {
        parent::__construct();

        $this->loadConfig([
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => _DB_NAME_,
            'username' => _DB_USER_,
            'password' => _DB_PASSWD_,
            'storage' => _PS_MODULE_DIR_ . 'fuzzysearch/indexes',
        ]);

        $this->fuzziness            = (Configuration::get('FUZZY_SEARCH_FUZZINESS', false, false, false, true)?true:false);
        $this->fuzzy_prefix_length  = Configuration::get('FUZZY_SEARCH_PREFIX_LENGTH', false, false, false, 2);
        $this->fuzzy_max_expansions = Configuration::get('FUZZY_SEARCH_MAX_EXPANSIONS', false, false, false, 50);
        $this->fuzzy_distance       = Configuration::get('FUZZY_SEARCH_DISTANCE', false, false, false, 50);
    }

    public function getWordlistByKeyword($keyword, $isLastWord = false)
    {
        $searchWordlist = "SELECT * FROM wordlist WHERE term like :keyword LIMIT 1";
        $stmtWord       = $this->index->prepare($searchWordlist);

        if ($this->asYouType && $isLastWord) {
            $searchWordlist = "SELECT * FROM wordlist WHERE term like :keyword ORDER BY length(term) ASC, num_hits DESC LIMIT 1";
            $stmtWord       = $this->index->prepare($searchWordlist);
            $stmtWord->bindValue(':keyword', mb_strtolower($keyword)."%");
        } else {
            $stmtWord->bindValue(':keyword', mb_strtolower($keyword));
        }
        $stmtWord->execute();
        $res = $stmtWord->fetchAll(PDO::FETCH_ASSOC);

        if ($this->fuzziness  && !isset($res[0])) {
            $res = $this->fuzzySearch($keyword);
        }

        $this->wordList = array_merge($this->wordList, array_column($res, 'term'));

        return $res;
    }

    public function searchAll($phrase)
    {
        $startTimer = microtime(true);
        
        $keywords = explode(' ', Search::sanitize($phrase, $this->context->language->id, false, $this->context->language->iso_code));
        $keywords   = new Collection($keywords);
        $keywords = $keywords->map(function ($keyword) {
            return $this->stemmer->stem($keyword);
        });

        $tfWeight  = 1;
        $dlWeight  = 0.5;
        $docScores = [];
        $count     = $this->totalDocumentsInCollection();

        foreach ($keywords as $index => $term) {
            $isLastKeyword = ($keywords->count() - 1) == $index;
            $df            = $this->totalMatchingDocuments($term, $isLastKeyword);
            $idf           = log($count / max(1, $df));

            foreach ($this->getAllDocumentsForKeyword($term, false, $isLastKeyword) as $document) {
                $docID = $document['doc_id'];
                $tf    = $document['hit_count'];
                $num   = ($tfWeight + 1) * $tf;
                $denom = $tfWeight
                    * ((1 - $dlWeight) + $dlWeight)
                    + $tf;
                $score             = $idf * ($num / $denom);
                $docScores[$docID] = isset($docScores[$docID]) ?
                    $docScores[$docID] + $score : $score;
            }
        }

        arsort($docScores);

        $docs = new Collection($docScores);

        $totalHits = $docs->count();
        $docs      = $docs->map(function ($doc, $key) {
            return $key;
        });


        $stopTimer = microtime(true);

        if ($this->isFileSystemIndex()) {
            return $this->filesystemMapIdsToPaths($docs)->toArray();
        }
        return [
            'ids'            => array_keys($docs->toArray()),
            'hits'           => $totalHits,
            'execution_time' => round($stopTimer - $startTimer, 7) * 1000 ." ms"
        ];
    }

    public function indexation($full = false, $id_product = null)
    {
        foreach (Shop::getShops(true, null,true) as $id_shop){

            try {
                $this->selectIndex('product.' . $id_shop . '.index');
                $index = $this->getIndex();
            } catch (Throwable $e) {
                $index = $this->createIndex('product.' . $id_shop . '.index', true);
            }

            if (!$full){
                $index->delete($id_product);
                $words =  Db::getInstance()->executeS('
                    SELECT sw.word, si.id_product as id
                    FROM ' . _DB_PREFIX_ . 'search_word sw
                    LEFT JOIN ' . _DB_PREFIX_ . 'search_index si ON sw.id_word = si.id_word
                    WHERE sw.id_shop = ' . pSQL($id_shop) . '
                        AND id_product = ' .pSQL($id_product). '
                ', true);

                foreach ($words as $word){
                    $index->insert($word);
                }

            } else {
                $index->prepareAndExecuteStatement('DROP TABLE IF EXISTS wordlist');
                $index->prepareAndExecuteStatement('DROP TABLE IF EXISTS doclist');
                $index->prepareAndExecuteStatement('DROP TABLE IF EXISTS fields');
                $index->prepareAndExecuteStatement('DROP TABLE IF EXISTS hitlist');
                $index->prepareAndExecuteStatement('DROP TABLE IF EXISTS info');

                $index = $this->createIndex('product.' . $id_shop . '.index', true);


                $index->query('
                    SELECT sw.word, si.id_product as id
                    FROM ' . _DB_PREFIX_ . 'search_word sw
                    LEFT JOIN ' . _DB_PREFIX_ . 'search_index si ON sw.id_word = si.id_word
                    WHERE sw.id_shop = ' . pSQL($id_shop) . '
                ');

                $index->run();
            }
        }
    }

}
