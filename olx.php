<?php

  error_reporting(E_ALL);

  /**
  * GET PRODUCTS FROM OLX
  */
  class olx
  {

    private $content; // string
    private $url = 'http://olx.pl/oferty/uzytkownik/--string--/'; // string
    private $paginationLinks = []; // array
    private $links = []; // array
    private $items = []; // array

    public function setContent($url = '')
    {
      if(!$url) $url = $this->url;
      $this->content = file_get_contents($url);
      $this->content = preg_replace('/\s\s+/', ' ', $this->content);
    }

    public function getContent()
    {
      return $this->content;
    }

    public function setPaginationLinks()
    {
      preg_match("!pageCount=(.*?)&!", $this->getContent(), $out);
      $pageCount = $out[1];

      for($i = 1; $i <= $pageCount; $i++) {
        $this->paginationLinks[] = 'http://olx.pl/oferty/?search%5Buser_id%5D=23275291&page=' . $i;
      }
    }

    public function getPaginationLinks()
    {
      return $this->paginationLinks;
    }

    public function setLinks()
    {
      foreach($this->getPaginationLinks() as $k => $v) {
        $this->setContent($v);
        preg_match_all('!\<a class="thumb vtop inlblk rel tdnone linkWithHash scale4 detailsLink " href="(.*?)"!', $this->getContent(), $out);

        foreach($out[1] as $k => $v) {
          $this->links[] = trim($v);
        }
      }
    }

    public function getLinks()
    {
      return $this->links;
    }

    public function items()
    {
      foreach($this->getLinks() as $k => $v) {
        $this->setContent($v);

        // adres
        $data['url'] = $v;

        // tytuł
        preg_match('!<p class="xx\-large fbold lheight26 pdingtop10">(.*?)<\/p>!', $this->getContent(), $out);
        $data['title'] = trim($out[1]);

        // cena
        preg_match('!<strong class="xxxx\-large margintop7 block not\-arranged">(.*?) zł!', $this->getContent(), $out);
        $data['price'] = trim($out[1]);

        // obrazek
        preg_match('!<meta property="og:image" content="(.*?)"\/>!', $this->getContent(), $out);
        $data['img'] = trim($out[1]);

        print_R($data);
        echo '<br><br>';
        flush();
      }
    }

  }

  $olx = new olx;
  $olx->setContent();
  $olx->setPaginationLinks();
  $olx->setLinks();
  $olx->items();

?>
