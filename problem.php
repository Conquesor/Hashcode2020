<?php

solveProblem();

function solveProblem()
{
    $filenames = [
        //'a_example',
        //'b_read_on',
        //'c_incunabula',
        'd_tough_choices',
        //'e_so_many_books',
        //'f_libraries_of_the_world',
    ];

    foreach ($filenames as $filename) {
        $problemSolver = new Hashcode($filename);
        $problemSolver->prepareInputData();
        $problemSolver->setLibraryOrder();
        // @ToDo: Check for inefficient libraries (days not used, score scored, get better fit by calculating possible score of remaining libraries
        $problemSolver->createOutputFile();
    }

    echo "Done new!";
}

class Hashcode
{
    private $filename;

    private $numberOfBooks;
    private $numberOfLibraries;
    private $numberOfDays;

    private $books = [];

    private $libraries = [];

    private $selectedLibraries = [];

    function __construct($filename)
    {
        $this->filename = $filename;
    }

    function prepareInputData()
    {
        $content = preg_replace(
            '/\n$/',
            '',
            file_get_contents('input/' . $this->filename . '.txt')
        );

        $set = explode("\n", $content);

        $generalInfo = explode(" ", $set[0]);

        $this->numberOfBooks = $generalInfo[0];
        $this->numberOfLibraries = $generalInfo[1];
        $this->numberOfDays = $generalInfo[2];

        unset($set[0]);

        $books = explode(" ", $set[1]);

        foreach ($books as $key => $bookScore) {
            $this->books[$key]['bookScore'] = $bookScore;
            $this->books[$key]['libraries'] = [];
        }

        unset($set[1]);

        $libraryKey = ['key' => 0, 'indicator' => 0];

        foreach ($set as $libraryLine) {
            $libraryInfo = explode(" ", $libraryLine);

            if ($libraryKey['indicator'] == 0) {
                $this->libraries[$libraryKey['key']]['libraryKey'] = $libraryKey['key'];
                $this->libraries[$libraryKey['key']]['numberOfBooks'] = $libraryInfo[0];
                $this->libraries[$libraryKey['key']]['signUpDays'] = $libraryInfo[1];
                $this->libraries[$libraryKey['key']]['shippingAmount'] = $libraryInfo[2];

                $libraryKey['indicator'] = 1;
            } else {

                $books = [];

                foreach ($libraryInfo as $bookId) {
                    $books[$bookId] = $this->books[$bookId]['bookScore'];
                    $this->books[$bookId]['libraries'][] = $libraryKey['key'];
                }

                $this->libraries[$libraryKey['key']]['books'] = $books;
                $libraryKey['indicator'] = 0;
                $libraryKey['key']++;
            }
        }

        foreach ($this->libraries as $key => $library) {
            $totalScore = 0;
            $books = [];

            foreach ($library['books'] as $bookId => $bookScore) {
                $books[$bookId] = $this->books[$bookId]['bookScore'];
                $totalScore += $this->books[$bookId]['bookScore'];
            }

            $this->libraries[$key]['totalScore'] = $totalScore;
            $this->libraries[$key]['efficiency'] = $this->calculateEfficiency($totalScore, $library['numberOfBooks'], $library['shippingAmount']);

            arsort($books);

            $this->libraries[$key]['books'] = $books;
        }

        $test = 0;
    }

    function setLibraryOrder()
    {
        $this->libraries = $this->sortByEfficiency();

        $days = 0;
        $stillAdd = true;

        while ($stillAdd && count($this->libraries) > 0) {
            $count = count($this->libraries);

            if ($count < 20000) {
                $test = 0;
            }

            if ($count < 10000) {
                $test = 0;
            }

            if ($count < 5000) {
                $test = 0;
            }

            if ($count < 1000) {
                $test = 0;
            }
            $libraries = array_reverse($this->libraries);
            $library = array_pop($libraries);
            $this->libraries = array_reverse($libraries);

            $stillAdd = $days + $library['signUpDays'] < $this->numberOfDays;

            //$library = $this->setBookOrder($library);

            $days += $library['signUpDays'];
            $this->selectedLibraries[$library['libraryKey']] = $library;

            //$this->libraries = $this->sortByEfficiency();
        }
    }

    function setBookOrder($library)
    {
        $bookCount = 0;
        $bookOrder = [];

        foreach ($library['books'] as $key => $bookScore) {
            $bookOrder[] = $key;
            $bookCount++;

            foreach ($this->books[$key]['libraries'] as $libraryKey) {
                if(array_key_exists($libraryKey, $this->libraries)) {
                    $bookLibrary = $this->libraries[$libraryKey];

                    $bookLibrary['totalScore'] -= $bookScore;

                    unset($bookLibrary['books'][$key]);

                    $bookLibrary['efficiency'] = $this->calculateEfficiency(
                        $bookLibrary['totalScore'],
                        $bookLibrary['numberOfBooks'],
                        $bookLibrary['shippingAmount']
                    );

                    $this->libraries[$libraryKey] = $bookLibrary;
                }
            }
        }

        $library['bookCount'] = $bookCount;
        $library['bookOrder'] = $bookOrder;

        return $library;
    }

    function calculateEfficiency($score, $bookNumber, $shippingAmount)
    {
        return $score / $bookNumber / ($bookNumber / $shippingAmount);
    }

    function sortByEfficiency()
    {
        $libraries = $this->libraries;

        $sortedLibraries = [];

        foreach ($libraries as $key => $library) {
            $sortedLibraries[$key] = $library['efficiency'];
        }

        arsort($sortedLibraries);

        $libraries = [];

        foreach ($sortedLibraries as $key => $eff) {
            $libraries[$key] = $this->libraries[$key];
        }

        return $libraries;
    }

    function createOutputFile()
    {
        $output = "";

        $output .= count($this->selectedLibraries) . "\n";

        foreach ($this->selectedLibraries as $libraryKey => $library) {
            $output .= $libraryKey . " " . $library['bookCount'] . "\n";
            $output .= implode(" ", $library['bookOrder']) . "\n";
        }

        file_put_contents('output/' . $this->filename . '_out.txt', rtrim($output, "\n"));
    }
}